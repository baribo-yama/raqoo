<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class ReservationController extends Controller
{
    // コンストラクタで認証チェックを追加
    public function __construct()
    {
        $this->middleware('auth');
    }

    // 予約申請フォーム表示(MVP版)
    public function create(Request $request)
    {
        // デバッグ用：受け取った値を確認
        Log::info('予約フォーム表示', [
            'room_id' => $request->room_id,
            'date' => $request->date,
            'period' => $request->period,
            'all_params' => $request->all(),
        ]);

        $selectedRoom = $request->room_id ? Room::find($request->room_id) : null;

        // dateがnullまたは空の場合はエラー
        if (!$request->date) {
            return redirect()->route('user.home.index')
                ->with('error', '日付が指定されていません。もう一度選択してください。');
        }

        $selectedDate = $request->date;
        $selectedPeriod = $request->period;

        // nullチェックを追加
        if (!$selectedRoom) {
            return redirect()->route('user.home.index')
                ->with('error', '教室がみつかりません。もう一度選択してください。');
        }

        return view('user.reservation-form', compact('selectedRoom', 'selectedDate', 'selectedPeriod'));
    }

    // 予約確認画面表示（MVP版）
    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date|after_or_equal:today',
            'period' => 'required|integer|min:1|max:6',
            'purpose' => 'required|string|max:100',
            'members' => 'nullable|string',
        ]);

        $selectedRoom = Room::find($validated['room_id']);

        // 重複予約チェック
        $existing = Reservation::where('room_id', $validated['room_id'])
            ->where('date', $validated['date'])
            ->where('period', $validated['period'])
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($existing) {
            return back()->withErrors(['error' => 'この時間帯はすでに予約されています。']);
        }

        // ビューで使いやすい形に整理
        $reservationData = [
        'room_id' => $validated['room_id'],
        'room_name' => $selectedRoom->name,
        'date' => $validated['date'],
        'period' => $validated['period'],
        'purpose' => $validated['purpose'],
        'members' => $validated['members'],
        'user_name' => Auth::user()->name,
        'student_id' => Auth::user()->student_id,
        ];

        // 将来実装予定：利用人数計算
        $memberCount = 1; // 代表者分
        if (!empty($validated['members'])) {
            $members = preg_split('/[\n\r]+/', trim($validated['members']));
            $memberCount += count(array_filter($members, 'trim'));
        }

        return view('user.reservation-confirm', compact('reservationData', 'selectedRoom', 'memberCount'));
    }

    // 予約申請保存（MVP版）
    public function store(Request $request)
    {
        // デバッグ用：リクエストデータをログに記録
        Log::info('予約申請開始', [
            'user_id' => Auth::id(),
            'room_id' => $request->room_id,
            'date' => $request->date,
            'period' => $request->period,
            'purpose' => $request->purpose
        ]);

        // バリデーション（confirmを経由しない場合もあるため　）
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date|after_or_equal:today',
            'period' => 'required|integer|min:1|max:6',
            'purpose' => 'required|string|max:500',
            'members' => 'nullable|string',
        ]);

        // 重複予約チェック
        $existing = Reservation::where('room_id', $validated['room_id'])
            ->where('date', $validated['date'])
            ->where('period', $validated['period'])
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        // storeでのチェック：データ整合性保証
        if ($existing) {
            return back()->withErrors(['error' => 'この時間帯はすでに予約されています。']);
        }

        // 予約作成
        $reservation = Reservation::create([
            'user_id' => Auth::id(),
            'room_id' => $validated['room_id'],    // $request->room_id ではなく
            'date' => $validated['date'],          // $request->date ではなく
            'period' => $validated['period'],      // $request->period ではなく
            'purpose' => $validated['purpose'],
            'members' => $validated['members'],
            'status' => 'pending',
        ]);

        // 予約作成成功ログを追加
        Log::info('予約申請完了', [
            'reservation_id' => $reservation->id,
            'user_id' => Auth::id(),
            'room_id' => $validated['room_id'],
            'date' => $validated['date'],
            'period' => $validated['period'],
            'status' => 'pending'
        ]);

        // 予約申請成功メッセージ
        return redirect()->route('user.home.index')
            ->with('success', '予約申請を送信しました。承認をお待ちください。');
    }

    // 予約一覧表示
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

    // 予約詳細表示
    public function show(Reservation $reservation)
    {
        // 自分の予約のみ表示可能
        if ($reservation->user_id !== Auth::id()) {
            abort(403);
        }

        return view('user.reservation-detail', compact('reservation'));
    }

    // 予約キャンセル
    public function cancel(Reservation $reservation)
    {
        // 自分の予約のみキャンセル可能
        if ($reservation->user_id !== Auth::id()) {
            abort(403);
        }

        // キャンセル可能な状態チェック
        if (!in_array($reservation->status, ['pending', 'approved'])) {
            return back()->withErrors(['error' => 'この予約はキャンセルできません。']);
        }

        $reservation->update(['status' => 'cancelled']);

        return redirect()->route('user.reservations.index')
            ->with('success', '予約をキャンセルしました。');
    }

    // 利用履歴表示
    public function history()
    {
        $historyReservations = Reservation::where(function ($query) {
            $query->where('date', '<', Carbon::today())
                ->orWhereIn('status', ['completed', 'cancelled', 'rejected']);
        })
            ->where('user_id', Auth::id())
            ->with('room')
            ->orderBy('date', 'desc')
            ->paginate(10);

        return view('user.reservations-history', compact('historyReservations'));
    }
}
