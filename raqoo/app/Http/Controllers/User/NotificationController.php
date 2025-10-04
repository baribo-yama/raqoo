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
        // 通知がない場合は空のコレクションを渡す
        // 実際のデータベースから取得する場合は以下のコメントアウトを解除
        /*
        $notifications = Notification::where(function($query) {
            $query->whereNull('user_id') // 全体通知
                  ->orWhere('user_id', Auth::id()); // 個人宛通知
        })
        ->orderBy('created_at', 'desc')
        ->paginate(10);
        */
        
        // 現在はサンプルデータをビューで表示しているため、空を渡す
        $notifications = collect();

        return view('user.notification', compact('notifications'));
    }
}