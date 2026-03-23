<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomLesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * 予約申請フォームの表示
     * ★ フォームを開いた瞬間に 'selecting'（選択中）レコードを作成してスロットをロック
     */
    public function create(Request $request)
    {
        // ★ DB操作前にdate/periodをバリデーション
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date'    => 'required|date|after_or_equal:today',
            'period'  => 'required|integer|min:1|max:6',
        ]);

        $selectedRoom = Room::find($request->room_id);

        if (!$selectedRoom) {
            return redirect()->route('user.home.index')->with('error', '教室がみつかりません。');
        }

        $date   = $request->date;
        $room   = $selectedRoom;
        $period = (int) $request->period;

        // ★ 10分以上経過した期限切れselectingレコードを全体で削除
        Reservation::where('status', 'selecting')
            ->where('created_at', '<', now()->subMinutes(10))
            ->delete();

        // ★ 自分の既存selectingを全削除（別の時間帯を選び直した場合のクリーンアップ）
        Reservation::where('user_id', Auth::id())
            ->where('status', 'selecting')
            ->delete();

        // ★ トランザクション内でロックを取りながらselectingを作成（同時クリック競合防止）
        $result = DB::transaction(function () use ($room, $date, $period) {
            $alreadyTaken = Reservation::where('room_id', $room->id)
                ->where('date', $date)
                ->where('period', $period)
                ->where('user_id', '!=', Auth::id())
                ->whereIn('status', ['selecting', 'pending', 'reserved', 'in_use'])
                ->lockForUpdate()
                ->exists();

            $lessonExists = RoomLesson::where('room_id', $room->id)
                ->where('date', $date)
                ->where('period', $period)
                ->exists();

            if ($alreadyTaken || $lessonExists) {
                return false;
            }

            Reservation::create([
                'user_id' => Auth::id(),
                'room_id' => $room->id,
                'date'    => $date,
                'period'  => $period,
                'purpose' => '選択中',
                'status'  => 'selecting',
            ]);

            return true;
        });

        if (!$result) {
            return redirect()->route('user.home.index')
                ->with('error', 'その時間帯はすでに利用できません。');
        }

        // 1. 他の人の「予約」を取得（selectingも含める）
        $reservedPeriods = Reservation::where('room_id', $room->id)
            ->where('date', $date)
            ->where('status', '!=', 'cancelled')
            ->pluck('period')
            ->toArray();

        // 2. 管理者が登録した「授業」を取得
        $lessonPeriods = RoomLesson::where('room_id', $room->id)
            ->where('date', $date)
            ->pluck('period')
            ->toArray();

        return view('user.reservation-form', compact('room', 'date', 'period', 'reservedPeriods', 'lessonPeriods'));
    }

    /**
     * 予約内容の重複チェックと確認画面表示
     * ★ 2コマ連続予約の場合、追加コマ分のselectingもここで作成する
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

        $selectedRoom = Room::find($validated['room_id']);

        // 自分のselectingは除外して重複チェック（自分の選択中は上書きOK）
        $existing = Reservation::where('room_id', $validated['room_id'])
            ->where('date', $validated['date'])
            ->whereIn('period', $validated['periods'])
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) {
                $q->where('user_id', '!=', Auth::id())
                  ->orWhereNotIn('status', ['selecting']);
            })
            ->exists();

        $existingLesson = RoomLesson::where('room_id', $validated['room_id'])
            ->where('date', $validated['date'])
            ->whereIn('period', $validated['periods'])
            ->exists();

        if ($existing || $existingLesson) {
            return back()
                ->withErrors(['periods' => '選択された時間帯の中に、すでに予約済みまたは授業が登録されている枠が含まれています。'])
                ->withInput();
        }

        $periods = $validated['periods'];
        sort($periods);

        for ($i = 1; $i < count($periods); $i++) {
            if ($periods[$i] - $periods[$i - 1] !== 1) {
                return back()
                    ->withErrors(['periods' => '複数時限を予約する場合は、連続した時限のみ選択してください。'])
                    ->withInput();
            }
        }

        // ★ Copilot指摘対応: 2コマ予約の場合、追加コマ分のselectingをここで作成する
        // create()では最初にクリックした1コマ分しかselectingを作らないため、
        // 確認画面に進む時点で追加コマ分のselectingを補完する
        if (count($periods) > 1) {
            // 自分がselectingを持っていないperiodを抽出
            $existingSelectingPeriods = Reservation::where('user_id', Auth::id())
                ->where('status', 'selecting')
                ->where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->whereIn('period', $periods)
                ->pluck('period')
                ->toArray();

            $missingPeriods = array_diff($periods, $existingSelectingPeriods);

            foreach ($missingPeriods as $missingPeriod) {
                $created = DB::transaction(function () use ($validated, $missingPeriod) {
                    // 他人がすでに埋めていないか確認（行ロック）
                    $alreadyTaken = Reservation::where('room_id', $validated['room_id'])
                        ->where('date', $validated['date'])
                        ->where('period', $missingPeriod)
                        ->where('user_id', '!=', Auth::id())
                        ->whereIn('status', ['selecting', 'pending', 'reserved', 'in_use'])
                        ->lockForUpdate()
                        ->exists();

                    if ($alreadyTaken) {
                        return false;
                    }

                    Reservation::create([
                        'user_id' => Auth::id(),
                        'room_id' => $validated['room_id'],
                        'date'    => $validated['date'],
                        'period'  => $missingPeriod,
                        'purpose' => '選択中',
                        'status'  => 'selecting',
                    ]);

                    return true;
                });

                if (!$created) {
                    return back()
                        ->withErrors(['periods' => 'タッチの差で選択した時間帯が埋まってしまいました。もう一度選び直してください。'])
                        ->withInput();
                }
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
     * ★ 申請対象の全periodに対してselectingが有効か確認
     * ★ selectingレコードを削除してからpendingレコードを作成
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

        $periods = $validated['periods'];
        sort($periods);

        for ($i = 1; $i < count($periods); $i++) {
            if ($periods[$i] - $periods[$i - 1] !== 1) {
                return back()
                    ->withErrors(['error' => '複数時限を予約する場合は、連続した時限のみ選択してください。'])
                    ->withInput();
            }
        }

        // ★ 全period分のselectingが揃っているか確認（count比較）
        $matchingSelectingCount = Reservation::where('user_id', Auth::id())
            ->where('status', 'selecting')
            ->where('room_id', $validated['room_id'])
            ->where('date', $validated['date'])
            ->whereIn('period', $validated['periods'])
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();

        $hasValidSelecting = ($matchingSelectingCount === count($validated['periods']));

        if (!$hasValidSelecting) {
            return redirect()->route('user.home.index')
                ->with('error', '申請に失敗しました。選択から10分が経過したため、もう一度最初からやり直してください。');
        }

        return DB::transaction(function () use ($validated) {
            // ★ 自分のselectingレコードを削除（pendingに置き換えるため）
            Reservation::where('user_id', Auth::id())
                ->where('status', 'selecting')
                ->delete();

            // 保存直前の重複チェック（他人のレコードのみ）
            $existing = Reservation::where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->whereIn('period', $validated['periods'])
                ->where('status', '!=', 'cancelled')
                ->where('user_id', '!=', Auth::id())
                ->lockForUpdate()
                ->exists();

            $existingLesson = RoomLesson::where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->whereIn('period', $validated['periods'])
                ->lockForUpdate()
                ->exists();

            if ($existing || $existingLesson) {
                return redirect()->route('user.reservations.create', [
                    'room_id' => $validated['room_id'],
                    'date'    => $validated['date'],
                ])->withErrors(['error' => 'タッチの差で予約または授業が埋まってしまいました。']);
            }

            // pendingレコードを作成
            foreach ($validated['periods'] as $period) {
                Reservation::create([
                    'user_id' => Auth::id(),
                    'room_id' => $validated['room_id'],
                    'date'    => $validated['date'],
                    'period'  => (int) $period,
                    'purpose' => $validated['purpose'],
                    'members' => $validated['members'],
                    'status'  => 'pending',
                ]);
            }

            return redirect()->route('user.home.index')
                ->with('success', '予約申請を送信しました。承認をお待ちください。');
        });
    }

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

    public function show(Reservation $reservation)
    {
        if ($reservation->user_id !== Auth::id()) abort(403);
        return view('user.reservation-detail', compact('reservation'));
    }

    public function cancel(Reservation $reservation)
    {
        if ($reservation->user_id !== Auth::id()) abort(403);

        if (!in_array($reservation->status, ['pending', 'reserved'], true)) {
            return back()->withErrors(['error' => 'この予約はキャンセルできません。']);
        }

        $reservation->update(['status' => 'cancelled']);

        return redirect()->route('user.reservations.index')
            ->with('success', '予約をキャンセルしました。');
    }

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

    public function myReservations(Request $request)
    {
        $request->validate([
            'date'  => 'nullable|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $date  = $request->query('date');
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

        $rows = $q->limit($limit)->get()->map(function ($r) {
            return [
                'id'         => $r->id,
                'room_name'  => optional($r->room)->name ?? '',
                'room_floor' => optional($r->room)->floor,
                'date'       => Carbon::parse($r->date)->format('Y-m-d'),
                'period'     => (int) $r->period,
                'status'     => (string) $r->status,
                'detail_url' => route('user.reservations.show', $r->id),
            ];
        });

        return response()->json($rows);
    }
}