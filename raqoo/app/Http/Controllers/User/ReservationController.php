<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomLesson; // モデルのインポート
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
     */
    public function create(Request $request)
    {
        $selectedRoom = $request->room_id ? Room::find($request->room_id) : null;

        if (!$request->date) {
            return redirect()->route('user.home.index')->with('error', '日付が指定されていません。');
        }
        if (!$selectedRoom) {
            return redirect()->route('user.home.index')->with('error', '教室がみつかりません。');
        }

        $date   = $request->date;
        $room   = $selectedRoom;
        $period = (int) $request->period;

        // 1. 他の人の「予約」を取得
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

        // 修正点：compactの中に 'lessonPeriods' を追加して、Blade側で使えるようにする
        return view('user.reservation-form', compact('room', 'date', 'period', 'reservedPeriods', 'lessonPeriods'));
    }

    /**
     * 予約内容の重複チェックと確認画面表示
     */
    public function check(Request $request)
    {
        $validated = $request->validate([
            'room_id'    => 'required|exists:rooms,id',
            'date'       => 'required|date|after_or_equal:today',
            'periods'    => 'required|array|min:1',
            'periods.*'  => 'integer|min:1|max:6',
            'purpose'    => 'required|string|max:100',
            'members'    => 'nullable|string',
        ]);

        $selectedRoom = Room::find($validated['room_id']);

        // 予約テーブルとの重複チェック
        $existing = Reservation::where('room_id', $validated['room_id'])
            ->where('date', $validated['date'])
            ->whereIn('period', $validated['periods'])
            ->where('status', '!=', 'cancelled')
            ->exists();

        // 授業テーブルとの重複チェック
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

        // 連続した時限かどうかのチェック
        for ($i = 1; $i < count($periods); $i++) {
            if ($periods[$i] - $periods[$i - 1] !== 1) {
                return back()
                    ->withErrors(['periods' => '複数時限を予約する場合は、連続した時限のみ選択してください。'])
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
            'periods'    => 'required|array|min:1',
            'periods.*'  => 'integer|min:1|max:6',
            'purpose'    => 'required|string|max:500',
            'members'    => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            // 保存直前の重複チェック
            $existing = Reservation::where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->whereIn('period', $validated['periods'])
                ->where('status', '!=', 'cancelled')
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

    // --- 以降の index, show, cancel, history, myReservations は変更なし ---

    public function index()
    {
        $reservations = Reservation::where('user_id', Auth::id())
            ->where('date', '>=', Carbon::today())
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