<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashBoardController extends Controller
{
    // 教室一覧画面（トップ）
    public function index()
    {
        $rooms = Room::all();

        // 授業の時間帯定義
        $periods = [
            (object)['id' => 1, 'time' => '9:10-10:40'],
            (object)['id' => 2, 'time' => '10:50-12:20'],
            (object)['id' => 3, 'time' => '13:00-14:30'],
            (object)['id' => 4, 'time' => '14:40-16:10'],
            (object)['id' => 5, 'time' => '16:20-17:50'],
            (object)['id' => 6, 'time' => '18:00-19:30'],
        ];

        // 承認待ちの予約を取得（並び順が重要）
        $rawReservations = Reservation::with(['user', 'room'])
            ->where('status', 'pending')
            ->orderBy('date')     // 日付順
            ->orderBy('room_id')  // 教室順
            ->orderBy('period')   // 時限順
            ->get();

        // 連続予約をまとめる処理を実行
        $reservations = $this->groupReservations($rawReservations);

        return view('admin.dashboard', compact('rooms', 'periods', 'reservations'));
    }

    /**
     * 連続する予約をグループ化するメソッド
     */
    private function groupReservations($reservations)
    {
        $grouped = [];
        $temp = null;

        foreach ($reservations as $res) {
            // 日付を「Y-m-d」形式の文字列に統一
            $resDate = $res->date instanceof Carbon ? $res->date->format('Y-m-d') : substr($res->date, 0, 10);
            
            if ($temp) {
                // 前回保存したデータの日付
                $tempDate = $temp['date_raw'];

                // 「同じユーザー」かつ「同じ日」かつ「同じ教室」かつ「時限が連続」かチェック
                if (
                    $temp['user_id'] == $res->user_id &&
                    $tempDate === $resDate &&
                    $temp['room_id'] == $res->room_id &&
                    (int)end($temp['periods']) + 1 === (int)$res->period
                ) {
                    // グループに追加
                    $temp['periods'][] = (int)$res->period;
                    $temp['ids'][] = $res->id; 
                    continue; 
                } else {
                    // 途切れたので前のグループを確定
                    $grouped[] = $this->formatGroup($temp);
                    $temp = null;
                }
            }

            // 新しいグループの開始
            $temp = [
                'user' => $res->user,
                'user_id' => $res->user_id,
                'room' => $res->room,
                'room_id' => $res->room_id,
                'date_raw' => $resDate,     // 比較用
                'date_display' => $resDate, // 表示用（ここの名前と合わせる必要がありました）
                'periods' => [(int)$res->period],
                'purpose' => $res->purpose,
                'ids' => [$res->id],
            ];
        }

        // 最後のグループを追加
        if ($temp) {
            $grouped[] = $this->formatGroup($temp);
        }

        return $grouped;
    }

    /**
     * グループデータを表示用に整形する
     */
    private function formatGroup($group)
    {
        // periods配列 (例: [3, 4, 5]) を "3~5" に変換
        $start = $group['periods'][0];
        $end = end($group['periods']);
        
        if ($start == $end) {
            $periodDisplay = "{$start}";
        } else {
            $periodDisplay = "{$start}~{$end}";
        }

        // Bladeで使いやすいオブジェクト形式に変換
        return (object)[
            'user' => $group['user'],
            'room' => $group['room'],
            
            // ▼▼▼ 修正箇所: $group['date'] ではなく $group['date_display'] を使用 ▼▼▼
            'date' => $group['date_display'], 
            
            'period' => $periodDisplay,
            'purpose' => $group['purpose'],
            'ids' => implode(',', $group['ids']),
            'count' => count($group['ids']),
        ];
    }
}