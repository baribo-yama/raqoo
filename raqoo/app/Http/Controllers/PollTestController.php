<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PollTestController extends Controller
{
    // テストページを返す
    public function pollPage()
    {
        return view('poll_test');
    }

    // long-poll receive: GET /long-poll/receive?last_id=NN
    public function receive(Request $request)
    {
        $lastId = (int) $request->query('last_id', 0);
        $timeout = 20;
        $start = time();

        while (true) {
            $messages = Cache::get('lp_messages', []);
            $new = array_filter($messages, function ($m) use ($lastId) {
                return isset($m['id']) && $m['id'] > $lastId;
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
    public function send(Request $request)
    {
        $text = $request->input('text', null);
        if (is_null($text) && $request->getContent()) {
            $payload = json_decode($request->getContent(), true);
            if (is_array($payload) && isset($payload['text'])) {
                $text = $payload['text'];
            }
        }

        if (!$text || trim($text) === '') {
            return response()->json(['ok' => false, 'error' => 'メッセージが空です'], 400);
        }

        $messages = Cache::get('lp_messages', []);
        $nextId = count($messages) + 1;
        $entry = [
            'id' => $nextId,
            'text' => $text,
            'created_at' => now()->toDateTimeString(),
        ];

        $messages[] = $entry;
        Cache::put('lp_messages', $messages, 60 * 60);

        return response()->json(['ok' => true, 'message' => $entry, 'last_id' => $nextId]);
    }

}
