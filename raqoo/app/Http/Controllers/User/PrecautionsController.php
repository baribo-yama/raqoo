<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PrecautionsController extends Controller
{
    /**
     * 注意事項（同意付き）表示
     * - next が明示されている予約フローの時だけ「次回から表示しない」を適用してスキップ
     */
    public function show(Request $request)
    {
        $fallback = route('user.home.index', [], false);

        $hasExplicitNext = $request->has('next');
        $next = $this->sanitizeNext($request->query('next'), $fallback);

        // ✅ 「次回から表示しない」は予約フロー（next付き）のときだけスキップ
        if ($hasExplicitNext && session('precautions_skip', false)) {
            return redirect($next);
        }

        return view('user.precautions', [
            'showAgree' => true,
            'next'      => $next,
            'startStep' => 1,
        ]);
    }

    /**
     * 同意POST
     * - skip_next_time がONなら次回以降スキップ
     * - next は内部パスに限定して安全にリダイレクト
     */
    public function agree(Request $request)
    {
        $validated = $request->validate([
            'next'           => ['nullable', 'string', 'max:500'],
            'skip_next_time' => ['nullable', 'boolean'],
        ]);

        $fallback = route('user.home.index', [], false);
        $next = $this->sanitizeNext($validated['next'] ?? null, $fallback);

        // ✅ 同意済み時刻（履歴用）
        session([
            'precautions_agreed_at' => now()->toDateTimeString(),
        ]);

        // ✅ 次回から表示しない（チェックされてたら保存）
        if ($request->boolean('skip_next_time')) {
            session(['precautions_skip' => true]);
        } else {
            session()->forget('precautions_skip');
        }

        return redirect($next);
    }

    /**
     * next を「内部相対パス」に制限する
     * - 許可: /xxx?yyy=zzz
     * - 拒否: //evil.com, http(s)://..., javascript:...
     */
    private function sanitizeNext(?string $next, string $fallback): string
    {
        $next = is_string($next) ? trim($next) : '';

        if ($next === '') {
            return $fallback;
        }

        // ✅ 相対パスのみ許可（/ で始まる）
        if (str_starts_with($next, '/')) {
            // ⚠️ //evil.com はスキーム相対＝外部扱いなので拒否
            if (str_starts_with($next, '//')) {
                return $fallback;
            }
            return $next;
        }

        // それ以外（絶対URLやスキーム付き等）は拒否
        return $fallback;
    }
}
