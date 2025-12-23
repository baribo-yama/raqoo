<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PrecautionsController extends Controller
{
    public function show(Request $request)
    {
        $next = $this->sanitizeNext($request->query('next', route('user.home.index')));

        // ✅ 「次回から表示しない」を選んだ人だけスキップ
        if (session('precautions_skip', false) && $next) {
            return redirect($next);
        }

        return view('user.precautions', [
            'showAgree' => true,
            'next' => $next,
            'startStep' => 1,
        ]);
    }

    public function agree(Request $request)
    {
        $next = $this->sanitizeNext($request->input('next', route('user.home.index')));

        // ✅ 同意済み自体は保存（履歴用）
        session([
            'precautions_agreed_at' => now()->toDateTimeString(),
        ]);

        // ✅ 次回から表示しない（チェックされてたら保存）
        if ($request->boolean('skip_next_time')) {
            session(['precautions_skip' => true]);
        } else {
            // チェックしてなければ、以前の skip を解除したい場合
            session()->forget('precautions_skip');
        }

        return redirect($next ?: route('user.home.index'));
    }

    /**
     * next を内部URLに制限（外部URLに飛ばされるのを防ぐ）
     */
    private function sanitizeNext(?string $next): ?string
    {
        if (!$next) return null;

        // 相対パスならOK（/xxx）
        if (str_starts_with($next, '/')) {
            return $next;
        }

        // 絶対URLならhostチェックして同一hostだけ許可
        $u = parse_url($next);
        if (!$u) return route('user.home.index');

        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $host = $u['host'] ?? null;

        if ($host && $appHost && $host === $appHost) {
            $path = $u['path'] ?? '/';
            $query = isset($u['query']) ? ('?' . $u['query']) : '';
            return $path . $query;
        }

        // 外部は拒否
        return route('user.home.index');
    }
}
