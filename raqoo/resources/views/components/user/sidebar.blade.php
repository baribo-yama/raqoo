
    <div class="navigation">
        <ul>
            <li class="list">
                <a href="{{ route('user.home.index') }}">
                    <span class="icon"><ion-icon name="home-outline"></ion-icon></span>
                    <span class="title">ホーム</span>
                </a>
            </li>
            <li class="list">
                <a href="{{ route('user.reservations.index') }}">
                    <span class="icon"><ion-icon name="calendar-outline"></ion-icon></span>
                    <span class="title">予約一覧</span>
                </a>
            </li>
            <li class="list">
                <a href="{{ route('user.reservations.history') }}">
                    <span class="icon"><ion-icon name="time-outline"></ion-icon></span>
                    <span class="title">利用履歴</span>
                </a>
            </li>
            <li class="list">
                <a href="{{ route('user.notifications.index') }}">
                    <span class="icon">
                        <ion-icon name="notifications-outline"></ion-icon>
                        @if ($unreadCount > 0)
                            <span class="notification-badge">!</span>
                        @endif
                    </span>
                    <span class="title">お知らせ</span>
                </a>
            </li>
            <li class="list">
                <form method="POST" action="{{ route('logout') }}" style="margin: 0; width: 100%;">
                    @csrf
                    <button type="submit"
                        style="background: none; border: none; padding: 0; width: 100%; text-align: left; display: block; color: inherit; font: inherit; cursor: pointer;">
                        <span class="icon"><ion-icon name="log-out-outline"></ion-icon></span>
                        <span class="title">ログアウト</span>
                    </button>
                </form>
            </li>
        </ul>
    </div>

<style>
    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: red;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    .icon {
        position: relative;
    }
</style>
