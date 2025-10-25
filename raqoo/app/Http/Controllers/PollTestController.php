<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PollTestController extends Controller
{
    // 管理者用ページ
    public function pollPageAdmin()
    {
        return view('poll_testadd');
    }

    // 利用者用ページ
    public function pollPageUser()
    {
        return view('poll_testuser');
    }

    // receive: GET /long-poll/receive?last_id=NN&role=admin|user&client_id=...
    public function receive(Request $request)
    {
        $lastId = (int) $request->query('last_id', 0);
        $role = $request->query('role', 'user'); // 'admin' or 'user'
        $clientId = $request->query('client_id', null); // user 用に自分の client_id を指定

        $timeout = 20;
        $start = time();

        while (true) {
            $messages = Cache::get('lp_messages', []);
            $new = array_filter($messages, function ($m) use ($lastId, $role, $clientId) {
                if (!isset($m['id']) || $m['id'] <= $lastId) return false;

                // 管理者は利用者の 'msg' を見たい（全体）
                if ($role === 'admin') {
                    return ($m['type'] ?? '') === 'msg';
                }

                // 利用者は自分宛の 'ack' を受け取りたい
                if ($role === 'user') {
                    // 自分宛の ack を受け取る
                    if (($m['type'] ?? '') === 'ack' && isset($m['target_client_id'])) {
                        return $m['target_client_id'] === $clientId;
                    }
                    // （必要なら）ユーザー向けの全体通知もここで受け取れる
                    return false;
                }

                return false;
            });

            if (!empty($new)) {
                $ids = array_map(function ($m) { return $m['id']; }, $new);
                $maxId = max($ids);
                return response()->json([
                    'ok' => true,
                    'messages' => array_values($new),
                    'last_id' => $maxId,
                ]);
            }

            if (time() - $start > $timeout) {
                return response()->json([
                    'ok' => false,
                    'messages' => [],
                    'last_id' => $lastId,
                ]);
            }

            sleep(1);
        }
    }

    // send: POST /long-poll/send
    // 使い分け：
    // - 利用者: { role:'user', name:'...', text:'...', client_id:'...' }
    // - 管理者: { role:'admin', text:'...', ref_id:123, target_client_id:'...' }  (ack)
    public function send(Request $request)
    {
        $payload = $request->all();

        $role = $payload['role'] ?? 'user';

        // テキスト必須チェック（簡易）
        $text = isset($payload['text']) ? trim($payload['text']) : '';
        if ($text === '') {
            return response()->json(['ok' => false, 'error' => 'メッセージが空です'], 400);
        }

        // 安全に一意IDを採番（Cache::increment を使う）
        // 初回は null か 0 の場合があるので atomic increment を期待する
        if (!Cache::has('lp_next_id')) {
            Cache::put('lp_next_id', 0);
        }
        $nextId = Cache::increment('lp_next_id');

        // 既存メッセージ取得
        $messages = Cache::get('lp_messages', []);

        if ($role === 'admin') {
            // ack 送信（管理者）
            $refId = isset($payload['ref_id']) ? (int)$payload['ref_id'] : null;
            $targetClient = $payload['target_client_id'] ?? null;

            if (!$refId || !$targetClient) {
                return response()->json(['ok' => false, 'error' => 'ref_id と target_client_id が必要です'], 400);
            }

            $entry = [
                'id' => $nextId,
                'type' => 'ack',
                'sender_name' => '管理者',
                'client_id' => null,
                'text' => $text,
                'ref_id' => $refId,
                'target_client_id' => $targetClient,
                'created_at' => now()->toDateTimeString(),
            ];

        } else {
            // user 送信（利用者）
            $name = $payload['name'] ?? '匿名';
            $clientId = $payload['client_id'] ?? null;

            if (!$clientId) {
                return response()->json(['ok' => false, 'error' => 'client_id が必要です'], 400);
            }

            $entry = [
                'id' => $nextId,
                'type' => 'msg',
                'sender_name' => $name,
                'client_id' => $clientId,
                'text' => $text,
                'ref_id' => null,
                'target_client_id' => null,
                'created_at' => now()->toDateTimeString(),
            ];
        }

        $messages[] = $entry;
        // キャッシュに保存（TTL は運用に応じて調整）
        Cache::put('lp_messages', $messages, 60 * 60);

        return response()->json(['ok' => true, 'message' => $entry, 'last_id' => $nextId]);
    }
}
