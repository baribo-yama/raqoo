<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomLesson;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    private const ACTIVE_RESERVATION_STATUSES = ['pending', 'reserved', 'in_use'];
    private const SELECTING_EXPIRE_MINUTES = 10;
    private const MAX_RESERVABLE_DAYS = 14;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * 予約申請フォームの表示
     *
     * 役割：
     * - ホーム画面から渡された room_id / date / period を確認する
     * - 予約可能な範囲か判定する
     * - 予約済み時限・授業時限を取得してフォームへ渡す
     */
    public function create(Request $request)
    {
        $this->cleanupExpiredSelecting();

        $validated = $request->validate([
            'room_id'   => 'required|exists:rooms,id',
            'date'      => 'required|date|after_or_equal:today',
            'period'    => 'nullable|integer|min:1|max:6',
            'periods'   => 'nullable|array|max:2',
            'periods.*' => 'integer|min:1|max:6',
        ]);

        $date = $validated['date'];
        $periods = $this->getSelectedPeriodsFromRequest($validated);

        if (empty($periods)) {
            return redirect()
                ->route('user.home.index')
                ->with('error', '予約する時限が選択されていません。');
        }

        $period = min($periods);
        $room = Room::find($validated['room_id']);

        if (!$room) {
            return redirect()
                ->route('user.home.index')
                ->with('error', '教室がみつかりません。');
        }

        if ($this->isOverReservableDate($date)) {
            return redirect()
                ->route('user.home.index')
                ->with('error', '予約できるのは2週間先までです。');
        }

        if ($this->hasEndedPeriod($date, $periods)) {
            return redirect()
                ->route('user.home.index')
                ->with('error', '選択された時限はすでに終了しています。別の日時を選んでください。');
        }

        if ($this->hasUserActiveReservationBeforeEndTime($date)) {
            return redirect()
                ->route('user.home.index')
                ->with('error', '今日の予約の利用が終了してから新たに予約してください。');
        }

        if ($this->hasUserFutureReservationOnDate($date)) {
            return redirect()
                ->route('user.home.index')
                ->with('error', '指定した日付にはすでに予約があるため、新たに予約できません。');
        }

        if ($this->hasReservationConflict($room->id, $date, [$period], false)) {
            return redirect()
                ->route('user.home.index')
                ->with('error', '選択された時限はすでに予約されています。別の時間を選んでください。');
        }

        if ($this->hasLessonConflict($room->id, $date, [$period], false)) {
            return redirect()
                ->route('user.home.index')
                ->with('error', '選択された時限には授業が登録されています。別の時間を選んでください。');
        }

        $reservedPeriods = $this->getReservedPeriods($room->id, $date);
        $lessonPeriods = $this->getLessonPeriods($room->id, $date);

        return view('user.reservation-form', compact(
            'room',
            'date',
            'period',
            'reservedPeriods',
            'lessonPeriods'
        ));
    }

    /**
     * 予約内容の重複チェックと確認画面表示
     */
    public function check(Request $request)
    {
        $this->cleanupExpiredSelecting();

        $validated = $request->validate([
            'room_id'   => 'required|exists:rooms,id',
            'date'      => 'required|date|after_or_equal:today',
            'periods'   => 'required|array|min:1|max:2',
            'periods.*' => 'integer|min:1|max:6',
            'purpose'   => 'required|string|max:100',
            'members'   => 'nullable|string',
        ]);

        $periods = $this->normalizePeriods($validated['periods']);

        if (!$this->arePeriodsConsecutive($periods)) {
            return back()
                ->withErrors(['periods' => '複数時限を予約する場合は、連続した時限のみ選択してください。'])
                ->withInput();
        }

        if ($this->hasEndedPeriod($validated['date'], $periods)) {
            return back()
                ->withErrors(['periods' => '選択された時限は既に終了しています。別の時間を選択してください。'])
                ->withInput();
        }

        $selectedRoom = Room::find($validated['room_id']);

        if (!$selectedRoom) {
            return redirect()
                ->route('user.home.index')
                ->with('error', '教室がみつかりません。');
        }

        if (
            $this->hasReservationConflict($validated['room_id'], $validated['date'], $periods, false)
            || $this->hasLessonConflict($validated['room_id'], $validated['date'], $periods, false)
        ) {
            return back()
                ->withErrors(['periods' => '選択された時間帯の中に、すでに予約済みまたは授業が登録されている枠が含まれています。'])
                ->withInput();
        }

        $lockResult = $this->createMissingSelectingReservations(
            $validated['room_id'],
            $validated['date'],
            $periods
        );

        if (!$lockResult) {
            return back()
                ->withErrors(['periods' => 'タッチの差で選択した時間帯が埋まってしまいました。もう一度選び直してください。'])
                ->withInput();
        }

        $reservationData = [
            'room_id'        => $validated['room_id'],
            'room_name'      => $selectedRoom->name,
            'date'           => $validated['date'],
            'periods'        => $periods,
            'period_display' => implode(', ', $periods),
            'purpose'        => $validated['purpose'],
            'members'        => $validated['members'] ?? '',
            'user_name'      => Auth::user()->name,
            'student_id'     => Auth::user()->student_id,
        ];

        return view('user.reservation-confirm', compact('reservationData', 'selectedRoom'));
    }

    /**
     * 予約の保存
     */
    public function store(Request $request)
    {
        $this->cleanupExpiredSelecting();

        $validated = $request->validate([
            'room_id'   => 'required|exists:rooms,id',
            'date'      => 'required|date|after_or_equal:today',
            'periods'   => 'required|array|min:1|max:2',
            'periods.*' => 'integer|min:1|max:6',
            'purpose'   => 'required|string|max:100',
            'members'   => 'nullable|string',
        ]);

        $periods = $this->normalizePeriods($validated['periods']);

        if (!$this->arePeriodsConsecutive($periods)) {
            return back()
                ->withErrors(['periods' => '複数時限を予約する場合は、連続した時限のみ選択してください。'])
                ->withInput();
        }

        if ($this->hasEndedPeriod($validated['date'], $periods)) {
            return back()
                ->withErrors(['periods' => '選択された時限は既に終了しています。別の時間を選択してください。'])
                ->withInput();
        }

        return DB::transaction(function () use ($validated, $periods) {
            $roomId = (int) $validated['room_id'];
            $date = $validated['date'];

            if (
                $this->hasReservationConflict($roomId, $date, $periods, true)
                || $this->hasLessonConflict($roomId, $date, $periods, true)
            ) {
                return redirect()
                    ->route('user.reservations.create', [
                        'room_id' => $roomId,
                        'date'    => $date,
                        'period'  => min($periods),
                    ])
                    ->withErrors(['periods' => 'タッチの差で予約または授業が埋まってしまいました。']);
            }

            if ($this->hasUserReservationConflict($roomId, $date, $periods)) {
                return redirect()
                    ->route('user.reservations.create', [
                        'room_id' => $roomId,
                        'date'    => $date,
                        'period'  => min($periods),
                    ])
                    ->withErrors(['periods' => '同時刻にすでにあなたの予約があるため予約できません。']);
            }

            $this->deleteOwnSelectingReservations($roomId, $date, $periods);

            foreach ($periods as $period) {
                Reservation::create([
                    'user_id' => Auth::id(),
                    'room_id' => $roomId,
                    'date'    => $date,
                    'period'  => $period,
                    'purpose' => $validated['purpose'],
                    'members' => $validated['members'] ?? '',
                    'status'  => 'pending',
                ]);
            }

            return redirect()
                ->route('user.home.index')
                ->with('success', '予約申請を送信しました。承認をお待ちください。');
        });
    }

    /**
     * 予約一覧
     */
    public function index()
    {
        $reservations = Reservation::where('user_id', Auth::id())
            ->where('date', '>=', Carbon::today())
            ->whereNotIn('status', ['selecting'])
            ->with('room')
            ->orderBy('date')
            ->orderBy('period')
            ->get();

        return view('user.reservations-index', compact('reservations'));
    }

    /**
     * 予約詳細
     */
    public function show(Reservation $reservation)
    {
        if ($reservation->user_id !== Auth::id()) {
            abort(403);
        }

        return view('user.reservation-detail', compact('reservation'));
    }

    /**
     * 学生側の予約キャンセル処理
     */
    public function cancel(Reservation $reservation)
    {
        if ($reservation->user_id !== Auth::id()) {
            abort(403);
        }

        if (!in_array($reservation->status, ['pending', 'reserved'], true)) {
            return back()->withErrors(['error' => 'この予約はキャンセルできません。']);
        }

        $reservation->update(['status' => 'cancelled']);

        return redirect()
            ->route('user.reservations.index')
            ->with('success', '予約をキャンセルしました。');
    }

    /**
     * 予約履歴
     */
    public function history()
    {
        $historyReservations = Reservation::where(function ($query) {
            $query->where('date', '<', Carbon::today())
                ->orWhereIn('status', ['finished', 'cancelled']);
        })
            ->where('user_id', Auth::id())
            ->with('room')
            ->orderBy('date', 'desc')
            ->paginate(10);

        return view('user.reservations-history', compact('historyReservations'));
    }

    /**
     * 予約一覧API
     */
    public function myReservations(Request $request)
    {
        $request->validate([
            'date'  => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $date = $request->query('date');
        $limit = (int) $request->query('limit', 50);

        $query = Reservation::with('room')
            ->where('user_id', Auth::id())
            ->whereNotIn('status', ['selecting'])
            ->orderBy('date')
            ->orderBy('period');

        if ($date) {
            $query->whereDate('date', $date);
        } else {
            $query->where('date', '>=', Carbon::today());
        }

        $reservations = $query->limit($limit)->get()->map(function ($reservation) {
            return [
                'id'         => $reservation->id,
                'room_name'  => optional($reservation->room)->name ?? '',
                'room_floor' => optional($reservation->room)->floor,
                'date'       => Carbon::parse($reservation->date)->format('Y-m-d'),
                'period'     => (int) $reservation->period,
                'status'     => (string) $reservation->status,
                'detail_url' => route('user.reservations.show', $reservation->id),
            ];
        });

        return response()->json($reservations);
    }

    /**
     * チェックボックスON/OFF時の selecting 作成・削除
     */
    public function toggleSelecting(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date'    => 'required|date|after_or_equal:today',
            'period'  => 'required|integer|min:1|max:6',
            'action'  => 'required|in:lock,unlock',
        ]);

        $this->cleanupExpiredSelecting();

        if ($validated['action'] === 'unlock') {
            $this->deleteOwnSelectingReservations(
                (int) $validated['room_id'],
                $validated['date'],
                [(int) $validated['period']]
            );

            return response()->json([
                'success' => true,
                'action'  => 'unlocked',
            ]);
        }

        $result = DB::transaction(function () use ($validated) {
            $roomId = (int) $validated['room_id'];
            $date = $validated['date'];
            $periods = [(int) $validated['period']];

            if (
                $this->hasReservationConflict($roomId, $date, $periods, true)
                || $this->hasLessonConflict($roomId, $date, $periods, true)
                || $this->hasUserReservationConflict($roomId, $date, $periods)
            ) {
                return false;
            }

            $alreadyMine = Reservation::where('user_id', Auth::id())
                ->where('status', 'selecting')
                ->where('room_id', $roomId)
                ->where('date', $date)
                ->where('period', $periods[0])
                ->where('created_at', '>=', $this->getSelectingValidFrom())
                ->exists();

            if (!$alreadyMine) {
                Reservation::create([
                    'user_id' => Auth::id(),
                    'room_id' => $roomId,
                    'date'    => $date,
                    'period'  => $periods[0],
                    'purpose' => '選択中',
                    'status'  => 'selecting',
                ]);
            }

            return true;
        });

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'その時間帯はすでに他の人が選択中、または予約できません。',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'action'  => 'locked',
        ]);
    }

    /**
     * 選択された時限の中に、既に終了時刻を過ぎているものがあるか判定する
     */
    private function hasEndedPeriod(string $date, array $periods): bool
    {
        if (!Carbon::parse($date)->isSameDay(Carbon::today())) {
            return false;
        }

        foreach ($periods as $period) {
            $endTime = config("period.{$period}.end_time");

            if (!$endTime) {
                continue;
            }

            $periodEndAt = Carbon::createFromFormat('Y-m-d H:i', "{$date} {$endTime}");

            if (now()->greaterThanOrEqualTo($periodEndAt)) {
                return true;
            }
        }

        return false;
    }

    /**
     * GETで渡された period / periods[] から選択時限を取得する
     */
    private function getSelectedPeriodsFromRequest(array $validated): array
    {
        $periods = $validated['periods'] ?? [];

        if (!empty($validated['period'])) {
            $periods[] = $validated['period'];
        }

        return $this->normalizePeriods($periods);
    }

    /**
     * 時限配列を整数化・重複削除・昇順ソートする
     */
    private function normalizePeriods(array $periods): array
    {
        $normalizedPeriods = array_map('intval', $periods);
        $normalizedPeriods = array_filter($normalizedPeriods, function ($period) {
            return $period >= 1 && $period <= 6;
        });

        $normalizedPeriods = array_values(array_unique($normalizedPeriods));

        sort($normalizedPeriods);

        return $normalizedPeriods;
    }

    /**
     * 複数時限が連続しているか判定する
     */
    private function arePeriodsConsecutive(array $periods): bool
    {
        if (count($periods) <= 1) {
            return true;
        }

        for ($index = 1; $index < count($periods); $index++) {
            if ($periods[$index] - $periods[$index - 1] !== 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * 予約可能な日付範囲を超えているか判定する
     */
    private function isOverReservableDate(string $date): bool
    {
        return Carbon::parse($date)->greaterThan(
            Carbon::today()->addDays(self::MAX_RESERVABLE_DAYS)
        );
    }

    /**
     * 今日の予約が終了していない状態か判定する
     */
    private function hasUserActiveReservationBeforeEndTime(string $date): bool
    {
        if (!Carbon::parse($date)->isSameDay(Carbon::today())) {
            return false;
        }

        $todayReservations = Reservation::where('user_id', Auth::id())
            ->whereDate('date', Carbon::today())
            ->whereIn('status', self::ACTIVE_RESERVATION_STATUSES)
            ->get();

        foreach ($todayReservations as $reservation) {
            $endTime = config("period.{$reservation->period}.end_time");

            if (!$endTime) {
                continue;
            }

            $periodEndAt = Carbon::createFromFormat(
                'Y-m-d H:i',
                Carbon::today()->toDateString() . " {$endTime}"
            );

            if (now()->lessThan($periodEndAt)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 明日以降の同日予約が既にあるか判定する
     */
    private function hasUserFutureReservationOnDate(string $date): bool
    {
        if (!Carbon::parse($date)->greaterThan(Carbon::today())) {
            return false;
        }

        return Reservation::where('user_id', Auth::id())
            ->whereDate('date', $date)
            ->whereIn('status', self::ACTIVE_RESERVATION_STATUSES)
            ->exists();
    }

    /**
     * 予約・選択中による競合があるか判定する
     *
     * 自分の selecting は、確認画面へ進む途中の一時ロックなので競合扱いしない。
     */
    private function hasReservationConflict(int $roomId, string $date, array $periods, bool $lockForUpdate): bool
    {
        $query = Reservation::where('room_id', $roomId)
            ->whereDate('date', $date)
            ->whereIn('period', $periods)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('user_id', '!=', Auth::id())
                        ->where(function ($query) {
                            $query->whereIn('status', self::ACTIVE_RESERVATION_STATUSES)
                                ->orWhere(function ($query) {
                                    $query->where('status', 'selecting')
                                        ->where('created_at', '>=', $this->getSelectingValidFrom());
                                });
                        });
                })
                    ->orWhere(function ($query) {
                        $query->where('user_id', Auth::id())
                            ->whereIn('status', self::ACTIVE_RESERVATION_STATUSES);
                    });
            });

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->exists();
    }

    /**
     * 授業登録による競合があるか判定する
     */
    private function hasLessonConflict(int $roomId, string $date, array $periods, bool $lockForUpdate): bool
    {
        $query = RoomLesson::where('room_id', $roomId)
            ->whereDate('date', $date)
            ->whereIn('period', $periods);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->exists();
    }

    /**
     * 同じユーザーが同時刻に別教室を予約していないか判定する
     */
    private function hasUserReservationConflict(int $roomId, string $date, array $periods): bool
    {
        return Reservation::where('user_id', Auth::id())
            ->whereDate('date', $date)
            ->whereIn('period', $periods)
            ->whereIn('status', self::ACTIVE_RESERVATION_STATUSES)
            ->where('room_id', '!=', $roomId)
            ->lockForUpdate()
            ->exists();
    }

    /**
     * 予約済み・選択中の時限一覧を取得する
     */
    private function getReservedPeriods(int $roomId, string $date): array
    {
        return Reservation::where('room_id', $roomId)
            ->whereDate('date', $date)
            ->where(function ($query) {
                $query->whereIn('status', self::ACTIVE_RESERVATION_STATUSES)
                    ->orWhere(function ($query) {
                        $query->where('status', 'selecting')
                            ->where('created_at', '>=', $this->getSelectingValidFrom());
                    });
            })
            ->pluck('period')
            ->map(fn($period) => (int) $period)
            ->toArray();
    }

    /**
     * 授業時限一覧を取得する
     */
    private function getLessonPeriods(int $roomId, string $date): array
    {
        return RoomLesson::where('room_id', $roomId)
            ->whereDate('date', $date)
            ->pluck('period')
            ->map(fn($period) => (int) $period)
            ->toArray();
    }

    /**
     * 足りない selecting レコードを作成する
     */
    private function createMissingSelectingReservations(int $roomId, string $date, array $periods): bool
    {
        $existingSelectingPeriods = Reservation::where('user_id', Auth::id())
            ->where('status', 'selecting')
            ->where('room_id', $roomId)
            ->whereDate('date', $date)
            ->whereIn('period', $periods)
            ->where('created_at', '>=', $this->getSelectingValidFrom())
            ->pluck('period')
            ->map(fn($period) => (int) $period)
            ->toArray();

        $missingPeriods = array_values(array_diff($periods, $existingSelectingPeriods));

        if (empty($missingPeriods)) {
            return true;
        }

        try {
            DB::transaction(function () use ($roomId, $date, $missingPeriods) {
                foreach ($missingPeriods as $period) {
                    if (
                        $this->hasReservationConflict($roomId, $date, [$period], true)
                        || $this->hasLessonConflict($roomId, $date, [$period], true)
                    ) {
                        throw new \RuntimeException('Selected period is already taken.');
                    }

                    Reservation::create([
                        'user_id' => Auth::id(),
                        'room_id' => $roomId,
                        'date'    => $date,
                        'period'  => $period,
                        'purpose' => '選択中',
                        'status'  => 'selecting',
                    ]);
                }
            });

            return true;
        } catch (\RuntimeException $error) {
            return false;
        }
    }

    /**
     * 自分の selecting レコードを削除する
     */
    private function deleteOwnSelectingReservations(int $roomId, string $date, array $periods): void
    {
        Reservation::where('user_id', Auth::id())
            ->where('status', 'selecting')
            ->where('room_id', $roomId)
            ->whereDate('date', $date)
            ->whereIn('period', $periods)
            ->delete();
    }

    /**
     * 期限切れの selecting レコードを削除する
     */
    private function cleanupExpiredSelecting(): void
    {
        Reservation::where('status', 'selecting')
            ->where('created_at', '<', $this->getSelectingValidFrom())
            ->delete();
    }

    /**
     * selecting が有効とみなされる最古時刻を返す
     */
    private function getSelectingValidFrom(): Carbon
    {
        return now()->subMinutes(self::SELECTING_EXPIRE_MINUTES);
    }
}