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
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * 期限切れの selecting レコードを削除する
     */
    private function cleanupExpiredSelecting(): void
    {
        Reservation::where('status', 'selecting')
            ->where('created_at', '<', now()->subMinutes(10))
            ->delete();
    }

    /**
     * 選択された時限の中に、既に終了時刻を過ぎているものがあるか判定する
     *
     * @param string $date 予約対象日 Y-m-d
     * @param array $periods 判定対象の時限IDの配列
     * @return bool ひとつでも終了済みの時限があればtrue
     */
    private function hasEndedPeriod(string $date, array $periods): bool
    {
        if (!Carbon::parse($date)->isSameDay(Carbon::today())) {
            return false;
        }

        foreach ($periods as $period) {
            $endTimeConfig = config("period.{$period}.end_time");

            if (!$endTimeConfig) {
                continue;
            }

            $periodEndAt = Carbon::createFromFormat('Y-m-d H:i', "{$date} {$endTimeConfig}");

            if (now()->greaterThanOrEqualTo($periodEndAt)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 予約申請フォームの表示
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'room_id'   => 'required|exists:rooms,id',
            'date'      => 'required|date|after_or_equal:today',
            'period'    => 'nullable|integer|min:1|max:6',
            'periods'   => 'nullable|array',
            'periods.*' => 'integer|min:1|max:6',
        ]);

        $this->cleanupExpiredSelecting();

        /*
            period が無い場合は periods[] の先頭を代表時限として扱う
        */
        $period = $validated['period'] ?? null;

        if (!$period && !empty($validated['periods'])) {
            $periods = array_map('intval', $validated['periods']);
            sort($periods);
            $period = $periods[0];
        }

        if (!$period) {
            return redirect()->route('user.home.index')
                ->with('error', '利用時限を選択してください。');
        }

        /*
            URL直叩きブロック処理
        */
        if ($this->hasEndedPeriod($validated['date'], [(int) $period])) {
            return redirect()
                ->route('user.home.index')
                ->with('error', '選択された時限はすでに終了しています。別の日時を選んでください。');
        }

        /*
            今日の予約がすでにある場合、
            その予約の終了時刻に達するまで、その日の新たな予約はできないようにする
        */
        $todayReservations = Reservation::where('user_id', Auth::id())
            ->whereDate('date', today())
            ->whereIn('status', ['pending', 'reserved', 'in_use'])
            ->get();

        foreach ($todayReservations as $reservation) {
            $endTimeConfig = config("period.{$reservation->period}.end_time");

            if (!$endTimeConfig) {
                continue;
            }

            $endTime = Carbon::createFromFormat(
                'Y-m-d H:i',
                today()->toDateString() . ' ' . $endTimeConfig
            );

            if (
                Carbon::parse($validated['date'])->isSameDay(Carbon::today()) &&
                now()->lessThan($endTime)
            ) {
                return redirect()
                    ->route('user.home.index')
                    ->with('error', '今日の予約の利用が終了してから新たに予約してください');
            }
        }

        /*
            明日以降の各日は1件までしか予約できない
        */
        if (Carbon::parse($validated['date'])->greaterThan(Carbon::today())) {
            $isExist = Reservation::where('user_id', Auth::id())
                ->where('date', $validated['date'])
                ->whereIn('status', ['pending', 'reserved', 'in_use'])
                ->exists();

            if ($isExist) {
                return redirect()
                    ->route('user.home.index')
                    ->with('error', '1件すでに予約が入っているのでできません');
            }
        }

        /*
            予約できるのは今日から14日先まで
        */
        $maxDate = Carbon::today()->addDays(14)->toDateString();

        if ($validated['date'] > $maxDate) {
            return redirect()->route('user.home.index')
                ->with('error', '予約できるのは2週間先までです。');
        }

        $selectedRoom = Room::find($validated['room_id']);

        if (!$selectedRoom) {
            return redirect()->route('user.home.index')
                ->with('error', '教室がみつかりません。');
        }

        $date = $validated['date'];
        $room = $selectedRoom;
        $period = (int) $period;

        /*
            予約済み・申請中・利用中・選択中の時限を取得
        */
        $reservedPeriods = Reservation::where('room_id', $room->id)
            ->where('date', $date)
            ->where(function ($query) {
                $query->whereIn('status', ['pending', 'reserved', 'in_use'])
                    ->orWhere(function ($q) {
                        $q->where('status', 'selecting')
                            ->where('created_at', '>=', now()->subMinutes(10));
                    });
            })
            ->pluck('period')
            ->toArray();

        /*
            授業で使用されている時限を取得
        */
        $lessonPeriods = RoomLesson::where('room_id', $room->id)
            ->where('date', $date)
            ->pluck('period')
            ->toArray();

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
        $validated = $request->validate([
            'room_id'    => 'required|exists:rooms,id',
            'date'       => 'required|date|after_or_equal:today',
            'periods'    => 'required|array|min:1|max:2',
            'periods.*'  => 'integer|min:1|max:6',
            'purpose'    => 'required|string|max:100',
            'members'    => 'nullable|string',
        ]);

        $this->cleanupExpiredSelecting();

        if ($this->hasEndedPeriod($validated['date'], $validated['periods'])) {
            return back()
                ->withErrors(['periods' => '選択された時限は既に終了しています。別の時間を選択してください。'])
                ->withInput();
        }

        $selectedRoom = Room::find($validated['room_id']);

        if (!$selectedRoom) {
            return redirect()->route('user.home.index')
                ->with('error', '教室がみつかりません。');
        }

        $periods = array_map('intval', $validated['periods']);
        sort($periods);

        for ($i = 1; $i < count($periods); $i++) {
            if ($periods[$i] - $periods[$i - 1] !== 1) {
                return back()
                    ->withErrors(['periods' => '複数時限を予約する場合は、連続した時限のみ選択してください。'])
                    ->withInput();
            }
        }

        $existing = Reservation::where('room_id', $validated['room_id'])
            ->where('date', $validated['date'])
            ->whereIn('period', $periods)
            ->where('user_id', '!=', Auth::id())
            ->where(function ($query) {
                $query->whereIn('status', ['pending', 'reserved', 'in_use'])
                    ->orWhere(function ($q) {
                        $q->where('status', 'selecting')
                            ->where('created_at', '>=', now()->subMinutes(10));
                    });
            })
            ->exists();

        $existingLesson = RoomLesson::where('room_id', $validated['room_id'])
            ->where('date', $validated['date'])
            ->whereIn('period', $periods)
            ->exists();

        if ($existing || $existingLesson) {
            return back()
                ->withErrors(['periods' => '選択された時間帯の中に、すでに予約済みまたは授業が登録されている枠が含まれています。'])
                ->withInput();
        }

        /*
            複数時限の場合、足りない selecting を追加で作成する
        */
        if (count($periods) > 1) {
            $existingSelectingPeriods = Reservation::where('user_id', Auth::id())
                ->where('status', 'selecting')
                ->where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->whereIn('period', $periods)
                ->where('created_at', '>=', now()->subMinutes(10))
                ->pluck('period')
                ->map(fn ($period) => (int) $period)
                ->toArray();

            $missingPeriods = array_values(array_diff($periods, $existingSelectingPeriods));

            try {
                DB::transaction(function () use ($validated, $missingPeriods) {
                    foreach ($missingPeriods as $missingPeriod) {
                        $alreadyTaken = Reservation::where('room_id', $validated['room_id'])
                            ->where('date', $validated['date'])
                            ->where('period', $missingPeriod)
                            ->where('user_id', '!=', Auth::id())
                            ->where(function ($query) {
                                $query->whereIn('status', ['pending', 'reserved', 'in_use'])
                                    ->orWhere(function ($q) {
                                        $q->where('status', 'selecting')
                                            ->where('created_at', '>=', now()->subMinutes(10));
                                    });
                            })
                            ->lockForUpdate()
                            ->exists();

                        $lessonExists = RoomLesson::where('room_id', $validated['room_id'])
                            ->where('date', $validated['date'])
                            ->where('period', $missingPeriod)
                            ->lockForUpdate()
                            ->exists();

                        if ($alreadyTaken || $lessonExists) {
                            throw new \RuntimeException('Selected period is already taken.');
                        }

                        Reservation::create([
                            'user_id' => Auth::id(),
                            'room_id' => $validated['room_id'],
                            'date'    => $validated['date'],
                            'period'  => $missingPeriod,
                            'purpose' => '選択中',
                            'status'  => 'selecting',
                        ]);
                    }
                });
            } catch (\RuntimeException $e) {
                return back()
                    ->withErrors(['periods' => 'タッチの差で選択した時間帯が埋まってしまいました。もう一度選び直してください。'])
                    ->withInput();
            }
        }

        $reservationData = [
            'room_id'        => $validated['room_id'],
            'room_name'      => $selectedRoom->name,
            'date'           => $validated['date'],
            'periods'        => $periods,
            'period_display' => implode(', ', $periods),
            'purpose'        => $validated['purpose'],
            'members'        => $validated['members'],
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
        $validated = $request->validate([
            'room_id'    => 'required|exists:rooms,id',
            'date'       => 'required|date|after_or_equal:today',
            'periods'    => 'required|array|min:1|max:2',
            'periods.*'  => 'integer|min:1|max:6',
            'purpose'    => 'required|string|max:100',
            'members'    => 'nullable|string',
        ]);

        $this->cleanupExpiredSelecting();

        if ($this->hasEndedPeriod($validated['date'], $validated['periods'])) {
            return back()
                ->withErrors(['periods' => '選択された時限は既に終了しています。別の時間を選択してください。'])
                ->withInput();
        }

        $periods = array_map('intval', $validated['periods']);
        sort($periods);

        for ($i = 1; $i < count($periods); $i++) {
            if ($periods[$i] - $periods[$i - 1] !== 1) {
                return back()
                    ->withErrors(['error' => '複数時限を予約する場合は、連続した時限のみ選択してください。'])
                    ->withInput();
            }
        }

        return DB::transaction(function () use ($validated, $periods) {
            /*
                保存直前の重複チェック

                - 他人の selecting / pending / reserved / in_use はブロック
                - 自分の pending / reserved / in_use もブロック
                - 自分の selecting は、このあと pending に更新するためブロックしない
            */
            $existing = Reservation::where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->whereIn('period', $periods)
                ->where(function ($query) {
                    $query->where(function ($q) {
                        $q->where('user_id', '!=', Auth::id())
                            ->where(function ($subQuery) {
                                $subQuery->whereIn('status', ['pending', 'reserved', 'in_use'])
                                    ->orWhere(function ($selectingQuery) {
                                        $selectingQuery->where('status', 'selecting')
                                            ->where('created_at', '>=', now()->subMinutes(10));
                                    });
                            });
                    })->orWhere(function ($q) {
                        $q->where('user_id', Auth::id())
                            ->whereIn('status', ['pending', 'reserved', 'in_use']);
                    });
                })
                ->lockForUpdate()
                ->exists();

            /*
                同一ユーザーの同時刻の別教室予約チェック
            */
            $conflict = Reservation::where('user_id', Auth::id())
                ->where('date', $validated['date'])
                ->whereIn('period', $periods)
                ->whereIn('status', ['pending', 'reserved', 'in_use'])
                ->where('room_id', '!=', $validated['room_id'])
                ->lockForUpdate()
                ->exists();

            /*
                授業が登録されているかチェック
            */
            $existingLesson = RoomLesson::where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->whereIn('period', $periods)
                ->lockForUpdate()
                ->exists();

            if ($existing || $existingLesson) {
                $firstPeriod = min($periods);

                return redirect()->route('user.reservations.create', [
                    'room_id' => $validated['room_id'],
                    'date'    => $validated['date'],
                    'period'  => $firstPeriod,
                ])->withErrors(['error' => 'タッチの差で予約または授業が埋まってしまいました。']);
            }

            if ($conflict) {
                return redirect()->route('user.reservations.create', [
                    'room_id' => $validated['room_id'],
                    'date'    => $validated['date'],
                    'period'  => $periods[0] ?? null,
                ])->withErrors(['error' => '同時刻にすでにあなたの予約があるため予約できません']);
            }

            foreach ($periods as $period) {
                /*
                    自分の selecting があれば pending に更新する。
                    なければ新規作成する。
                */
                $selectingReservation = Reservation::where('user_id', Auth::id())
                    ->where('room_id', $validated['room_id'])
                    ->where('date', $validated['date'])
                    ->where('period', $period)
                    ->where('status', 'selecting')
                    ->lockForUpdate()
                    ->first();

                if ($selectingReservation) {
                    $selectingReservation->update([
                        'purpose' => $validated['purpose'],
                        'members' => $validated['members'],
                        'status'  => 'pending',
                    ]);
                } else {
                    Reservation::create([
                        'user_id' => Auth::id(),
                        'room_id' => $validated['room_id'],
                        'date'    => $validated['date'],
                        'period'  => $period,
                        'purpose' => $validated['purpose'],
                        'members' => $validated['members'],
                        'status'  => 'pending',
                    ]);
                }
            }

            return redirect()->route('user.home.index')
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

        return redirect()->route('user.reservations.index')
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
     * スマホ用：自分の予約一覧API
     */
    public function myReservations(Request $request)
    {
        $request->validate([
            'date'  => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $date = $request->query('date');
        $limit = (int) $request->query('limit', 50);

        $q = Reservation::with('room')
            ->where('user_id', Auth::id())
            ->whereNotIn('status', ['selecting'])
            ->orderBy('date')
            ->orderBy('period');

        if ($date) {
            $q->whereDate('date', $date);
        } else {
            $q->where('date', '>=', Carbon::today());
        }

        $rows = $q->limit($limit)->get()->map(function ($reservation) {
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

        return response()->json($rows);
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
            Reservation::where('user_id', Auth::id())
                ->where('status', 'selecting')
                ->where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->where('period', $validated['period'])
                ->delete();

            return response()->json([
                'success' => true,
                'action' => 'unlocked',
            ]);
        }

        $result = DB::transaction(function () use ($validated) {
            $alreadyTaken = Reservation::where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->where('period', $validated['period'])
                ->where('user_id', '!=', Auth::id())
                ->where(function ($query) {
                    $query->whereIn('status', ['pending', 'reserved', 'in_use'])
                        ->orWhere(function ($q) {
                            $q->where('status', 'selecting')
                                ->where('created_at', '>=', now()->subMinutes(10));
                        });
                })
                ->lockForUpdate()
                ->exists();

            $lessonExists = RoomLesson::where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->where('period', $validated['period'])
                ->lockForUpdate()
                ->exists();

            if ($alreadyTaken || $lessonExists) {
                return false;
            }

            $alreadyMine = Reservation::where('user_id', Auth::id())
                ->where('status', 'selecting')
                ->where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->where('period', $validated['period'])
                ->where('created_at', '>=', now()->subMinutes(10))
                ->exists();

            if (!$alreadyMine) {
                Reservation::create([
                    'user_id' => Auth::id(),
                    'room_id' => $validated['room_id'],
                    'date'    => $validated['date'],
                    'period'  => $validated['period'],
                    'purpose' => '選択中',
                    'status'  => 'selecting',
                ]);
            }

            return true;
        });

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'その時間帯はすでに他の人が選択中です。',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'action' => 'locked',
        ]);
    }
}