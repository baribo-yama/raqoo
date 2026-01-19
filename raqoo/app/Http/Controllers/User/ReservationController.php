<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // 予約申請フォーム表示
    public function create(Request $request)
    {
        Log::info('予約フォーム表示', [
            'room_id' => $request->room_id,
            'date' => $request->date,
            'period' => $request->period,
        ]);

        $selectedRoom = $request->room_id ? Room::find($request->room_id) : null;

        if (!$request->date) {
            return redirect()->route('user.home.index')
                ->with('error', '日付が指定されていません。');
        }

        if (!$selectedRoom) {
            return redirect()->route('user.home.index')
                ->with('error', '教室がみつかりません。');
        }

        $date = $request->date;
        $room = $selectedRoom;
        // ▼▼▼ 修正：元の時限を変数として渡す ▼▼▼
        $period = $request->period; 
        
        return view('user.reservation-form', compact('room', 'date', 'period'));
    }

    // --- (check, store, index, show, cancel, history メソッドは変更なし) ---
    // ※ checkメソッド以降は前回のままでOKです
    
    /**
     * 確認画面表示
     */
    public function check(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date'    => 'required|date|after_or_equal:today',
            'periods' => 'required|array|min:1',
            'periods.*' => 'integer|min:1|max:6',
            'purpose' => 'required|string|max:100',
            'members' => 'nullable|string',
        ]);

        $selectedRoom = Room::find($validated['room_id']);

        $existing = Reservation::where('room_id', $validated['room_id'])
            ->where('date', $validated['date'])
            ->whereIn('period', $validated['periods'])
            ->where('status', 'approved')
            ->exists();

        if ($existing) {
            return back()->withErrors(['periods' => '選択された時間帯の中に、すでに予約済みの枠が含まれています。'])
                         ->withInput();
        }

        $periods = $validated['periods'];
        sort($periods);

        $reservationData = [
            'room_id' => $validated['room_id'],
            'room_name' => $selectedRoom->name,
            'date'    => $validated['date'],
            'periods' => $periods,
            'period_display' => implode(', ', $periods),
            'purpose' => $validated['purpose'],
            'members' => $validated['members'],
            'user_name' => Auth::user()->name,
            'student_id' => Auth::user()->student_id,
        ];

        return view('user.reservation-confirm', compact('reservationData', 'selectedRoom'));
    }

    public function store(Request $request)
    {
        // (前回のコードと同じため省略、変更不要)
        // トランザクション処理などはそのまま維持してください
        
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date'    => 'required|date|after_or_equal:today',
            'periods' => 'required|array|min:1',
            'periods.*' => 'integer|min:1|max:6',
            'purpose' => 'required|string|max:500',
            'members' => 'nullable|string',
        ]);

        $existing = Reservation::where('room_id', $validated['room_id'])
            ->where('date', $validated['date'])
            ->whereIn('period', $validated['periods'])
            ->where('status', 'approved')
            ->exists();

        if ($existing) {
            return redirect()->route('user.reservations.create', [
                'room_id' => $validated['room_id'],
                'date' => $validated['date']
            ])->withErrors(['error' => 'タッチの差で予約が埋まってしまいました。再度確認してください。']);
        }

        DB::transaction(function () use ($validated) {
            foreach ($validated['periods'] as $period) {
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
        });

        return redirect()->route('user.home.index')
            ->with('success', '予約申請を送信しました。承認をお待ちください。');
    }

    // index, show, cancel, history 等も変更なし
    public function index() { /*...*/ return view('user.reservations-index', /*...*/); }
    public function show(Reservation $reservation) { /*...*/ return view('user.reservation-detail', compact('reservation')); }
    public function cancel(Reservation $reservation) { /*...*/ }
    public function history() { /*...*/ return view('user.reservations-history', /*...*/); }
}