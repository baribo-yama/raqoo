<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TimeGuardMiddleware
{
    /**
     * 予約時間外にアクセスしようとした際に /unavailableページに飛ばす処理
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    public function handle(Request $request, Closure $next)
    {
        if (!config('app.time_guard_enabled')) {
            return $next($request);
        }
        // .envにAPP_FAKE_NOW="2026-5-14 08:00:00"を設定すると、
        // AppServiceProviderのCarbon::setTestNow()によりnow()がその時刻を返す
        $now = now(); // 現在時刻・曜日を取得する
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
