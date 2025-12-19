<?php

namespace App\View\Components\User;

use App\Models\Notification;
use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class Sidebar extends Component
{
    public function render(): View
    {
        $unreadCount = Auth::check()
        ? Notification::getUnreadCountForUser(Auth::id())
        : 0;

        return view('components.user.sidebar', ['unreadCount'=>$unreadCount]);
    }
}
