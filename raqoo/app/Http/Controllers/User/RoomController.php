<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        // 1. フォームからの入力を取得
        $date = $request->input('date');         // 日付
        $startPeriod = $request->input('start_period'); // 開始時限
        $endPeriod = $request->input('end_period');     // 終了時限
        $people = $request->input('people');            // 利用人数

        // ★追加修正: 開始時限が終了時限より大きい場合（例: 6〜2）、自動で入れ替える
        if ($startPeriod && $endPeriod && $startPeriod > $endPeriod) {
            $temp = $startPeriod;
            $startPeriod = $endPeriod;
            $endPeriod = $temp;
        }

        // 2. クエリの準備
        $query = Room::query();

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
        // 検索条件をフォームに残すために、入力値も一緒に渡します
        // （startPeriodなどは入れ替え後の正しい値が渡されます）
        return view('user.home', compact('rooms', 'date', 'startPeriod', 'endPeriod', 'people'));
    }
}