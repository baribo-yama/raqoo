<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // 通知一覧を表示する際にすべて既読にする
        Notification::where('user_id', Auth::id())->where('is_read', false)->update(['is_read' => true]);

        $notifications = Notification::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10); // 通知を10件ずつ取り出して、ページに分割して返す・

        // compact関数は['notifications' => $notifications]の短縮版
        // これをやるとblade側では$notificationsという変数名で受け取れる
        return view('user.notification', compact('notifications'));
    }
}
