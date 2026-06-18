@props(['unreadCount' => 0])

@php
/*
sidebar.blade.php

役割：
- ユーザー画面共通のサイドバーを表示する
- 現在表示中のページに active を付ける
- スマホ・タブレット用の開閉ボタンを表示する

注意：
- サイドバーの開閉処理は home.js 側で行う
- 開閉状態は body.nav-open / aria-expanded / data-open で管理する
- 各ページ側には .m-backdrop を配置する
*/
$unreadCount = (int) $unreadCount;

$isHomeActive = request()->routeIs('user.home.index');

$isReservationsActive = request()->routeIs(
'user.reservations.index',
'user.reservations.show'
);

$isHistoryActive = request()->routeIs('user.reservations.history');

$isTermsActive = request()->routeIs(
'user.terms',
'user.precautions',
'user.night.precautions'
);

$isNotificationsActive = request()->routeIs('user.notifications.index');
@endphp

{{-- =========================================================
    スマホ・タブレット用メニューボタン
    - 1024px以下でサイドバーを開閉する
    - aria-expanded は JavaScript 側で true / false を切り替える
========================================================= --}}
<button
    class="nav-toggle"
    type="button"
    aria-label="メニューを開閉"
    aria-expanded="false">
    <ion-icon name="menu-outline"></ion-icon>
</button>

{{-- =========================================================
    サイドナビゲーション
    - data-open は JavaScript 側で開閉状態を管理する
========================================================= --}}
<nav class="navigation" data-open="false" aria-label="ユーザーメニュー">
    <ul>
        {{-- ホーム --}}
        <li class="list {{ $isHomeActive ? 'active' : '' }}">
            <a
                href="{{ route('user.home.index') }}"
                @if ($isHomeActive) aria-current="page" @endif>
                <span class="icon">
                    <ion-icon name="home-outline"></ion-icon>
                </span>

                <span class="title">
                    ホーム
                </span>
            </a>
        </li>

        {{-- 予約一覧 --}}
        <li class="list {{ $isReservationsActive ? 'active' : '' }}">
            <a
                href="{{ route('user.reservations.index') }}"
                @if ($isReservationsActive) aria-current="page" @endif>
                <span class="icon">
                    <ion-icon name="calendar-outline"></ion-icon>
                </span>

                <span class="title">
                    予約一覧
                </span>
            </a>
        </li>

        {{-- 利用履歴 --}}
        <li class="list {{ $isHistoryActive ? 'active' : '' }}">
            <a
                href="{{ route('user.reservations.history') }}"
                @if ($isHistoryActive) aria-current="page" @endif>
                <span class="icon">
                    <ion-icon name="time-outline"></ion-icon>
                </span>

                <span class="title">
                    利用履歴
                </span>
            </a>
        </li>

        {{-- 利用規約 --}}
        <li class="list {{ $isTermsActive ? 'active' : '' }}">
            <a
                href="{{ route('user.terms') }}"
                @if ($isTermsActive) aria-current="page" @endif>
                <span class="icon">
                    <ion-icon name="document-text-outline"></ion-icon>
                </span>

                <span class="title">
                    利用規約
                </span>
            </a>
        </li>

        {{-- お知らせ --}}
        <li class="list {{ $isNotificationsActive ? 'active' : '' }}">
            <a
                href="{{ route('user.notifications.index') }}"
                @if ($isNotificationsActive) aria-current="page" @endif>
                <span class="icon notification-icon">
                    <ion-icon name="notifications-outline"></ion-icon>

                    @if ($unreadCount > 0)
                    <span class="notification-badge" aria-label="未読のお知らせがあります">
                        !
                    </span>
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
                class="sidebar-logout-form">
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