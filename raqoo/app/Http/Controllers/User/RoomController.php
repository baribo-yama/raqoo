<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Reservation;
use Illuminate\Support\Facades\Auth;

class RoomController extends Controller
{
    /**
     * ホーム画面：検索条件（日付・時限・人数・階数）を受け取り、条件に合う教室一覧を表示する
     */
    public function index(Request $request)
    {
        // 2週間の制限
        $maxDate = Carbon::today()->addDays(14)->toDateString();
        // 1. フォームからの入力を取得
        $date = $request->input('date');         // 日付
        $startPeriod = $request->input('start_period'); // 開始時限
        $endPeriod = $request->input('end_period');     // 終了時限
        $people = $request->input('people');            // 利用人数

        // 2週間を超えていたらエラー表示
        if ($request->date > $maxDate) {
            return redirect()->route('user.home.index')
                -> with('error', '予約できるのは2週間先までです。');
        }



        // ★追加: 階数フィルタの入力を取得
        $floor = $request->input('floor');

        // ↓何これ　いつ使うやつ？
        // 開始時限が終了時限より大きい場合（例: 6〜2）、自動で入れ替える
        // 検索フォームで指定された「利用時限（開始〜終了）」の入力が逆になっているときに、自動で正しい順序に直すためのコード
        if ($startPeriod && $endPeriod && $startPeriod > $endPeriod) {
            $temp = $startPeriod;
            $startPeriod = $endPeriod;
            $endPeriod = $temp;

            // ↓ 何これ　いつ使うやつ？
            // 自動修正したことをユーザーに通知（フラッシュメッセージ）
            session()->flash('info', '開始時限が終了時限より遅いため、自動的に順序を入れ替えました。');
        }

        // 2. クエリの準備
        // N+1問題対策として with('roomStatuses') を追加
        $query = Room::query()->with('roomStatuses');  // ここから先SQLを組み立てていくよ、って意味でquery()を書いてる。これなしでRoom::with('roomStatuses')にしても動作は変らない

        // ★修正: 階数の絞り込み処理（セキュリティ対策追加）
        // URL等で不正な値（例: abc, 999）が入力された場合は無視し、
        // 「1〜5」の正しい数値が来た場合のみ絞り込みを行う
        if ($floor && in_array((int)$floor, [1, 2, 3, 4, 5], true)) {
            $query->where('floor', $floor);
        }

        // --- 検索条件1: 人数 ---
        // 入力がある場合のみ、定員(capacity)と比較
        if ($people) {
            $query->where('capacity', '>=', $people);
        }

        // --- 検索条件2: 日付と時限 ---
        // 日付・開始・終了がすべて入力されている場合のみチェック
        if ($date && $startPeriod && $endPeriod) {

            // 「指定された期間内に、'available' (利用可能) ではないステータスを持つ部屋」を除外する
            // つまり、「期間中すべて available な部屋」だけが残る
            $query->whereDoesntHave('roomStatuses', function ($q) use ($date, $startPeriod, $endPeriod) {
                $q->where('date', $date)
                  ->whereBetween('period', [$startPeriod, $endPeriod]) // 開始〜終了の範囲
                  ->where('status', '!=', 'available'); // 利用可能じゃないデータを探す
            });
        }

        // 3. 結果を取得
        $rooms = $query->get();

        // 4. ビューにデータを渡す
        // ★追加: $floor も compact に追加してビューに渡す（ボタンのアクティブ判定に使うため）
        return view('user.home', compact('rooms', 'date', 'startPeriod', 'endPeriod', 'people', 'floor'));
    }
}
