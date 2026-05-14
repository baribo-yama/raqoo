@props(['unreadCount' => 0])

{{-- =========================================================
    スマホ・タブレット用メニューボタン
    - 1024px以下でサイドバーを開閉する
    - aria-expanded は JavaScript 側で true / false を切り替える
========================================================= --}}
<button
    class="nav-toggle"
    type="button"
    aria-label="メニューを開閉"
    aria-expanded="false"
>
    <ion-icon name="menu-outline"></ion-icon>
</button>

{{-- =========================================================
    サイドナビゲーション
    - data-open は JavaScript 側で開閉状態を管理する
========================================================= --}}
<nav class="navigation" data-open="false" aria-label="ユーザーメニュー">
    <ul>
        {{-- ホーム --}}
        <li class="list {{ request()->routeIs('user.home.index') ? 'active' : '' }}">
            <a href="{{ route('user.home.index') }}">
                <span class="icon">
                    <ion-icon name="home-outline"></ion-icon>
                </span>

                <span class="title">
                    ホーム
                </span>
            </a>
        </li>

        {{-- 予約一覧 --}}
        <li class="list {{ request()->routeIs('user.reservations.index') ? 'active' : '' }}">
            <a href="{{ route('user.reservations.index') }}">
                <span class="icon">
                    <ion-icon name="calendar-outline"></ion-icon>
                </span>

                <span class="title">
                    予約一覧
                </span>
            </a>
        </li>

        {{-- 利用履歴 --}}
        <li class="list {{ request()->routeIs('user.reservations.history') ? 'active' : '' }}">
            <a href="{{ route('user.reservations.history') }}">
                <span class="icon">
                    <ion-icon name="time-outline"></ion-icon>
                </span>

                <span class="title">
                    利用履歴
                </span>
            </a>
        </li>

        {{-- 利用規約 --}}
        <li class="list {{ request()->routeIs('user.terms') ? 'active' : '' }}">
            <a href="{{ route('user.terms') }}">
                <span class="icon">
                    <ion-icon name="document-text-outline"></ion-icon>
                </span>

                <span class="title">
                    利用規約
                </span>
            </a>
        </li>

        {{-- お知らせ --}}
        <li class="list {{ request()->routeIs('user.notifications.index') ? 'active' : '' }}">
            <a href="{{ route('user.notifications.index') }}">
                <span class="icon notification-icon">
                    <ion-icon name="notifications-outline"></ion-icon>

                    @if ($unreadCount > 0)
                        <span class="notification-badge">!</span>
                    @endif
                </span>

                <span class="title">
                    お知らせ
                </span>
            </a>
        </li>

        {{-- ログアウト --}}
        <li class="list">
            <form
                method="POST"
                action="{{ route('logout') }}"
                class="sidebar-logout-form"
            >
                @csrf

                <button type="submit" class="logout-btn">
                    <span class="icon">
                        <ion-icon name="log-out-outline"></ion-icon>
                    </span>

                    <span class="title">
                        ログアウト
                    </span>
                </button>
            </form>
        </li>
    </ul>
</nav>