<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TimeGuardMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    public function handle(Request $request, Closure $next)
    {
        // $now = now(); // 現在時刻・曜日を取得する
        // .envにAPP_FAKE_NOW="2026-5-14 08:00:00"のように、テストしたい時刻を設定してテストする
        $now = env('APP_FAKE_NOW') ? \Carbon\Carbon::parse(env('APP_FAKE_NOW')) : now();
        $time = $now->format('H:i'); // 現在時刻を24時間で表示する


        // 平日は9:10～19:30までしか利用できない
        // isWeekday()は「平日かどうか」をチェックする
        if ($now->isWeekday()) {  // もし今日（$now)が平日なら以下を実行する
            if ($time > '19:30' || $time < '09:10') {
                return redirect()->route('unavailable');
            }
        }

        // 休日は10:50～17:50までしか利用できない
        if ($now->isWeekend()) {
            if ($time > '17:50' || $time < '10:50') {
                return redirect()->route('unavailable');
            }
        }
        return $next($request);
    }
}
