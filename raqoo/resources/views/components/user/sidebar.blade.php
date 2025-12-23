    <button class="nav-toggle" type="button" aria-label="メニューを開閉" aria-expanded="false">
        <ion-icon name="menu-outline"></ion-icon>
    </button>

    <div class="navigation" data-open="false">
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
                <a href="{{ route('user.terms') }}">
                    <span class="icon"><ion-icon name="document-text-outline"></ion-icon></span>
                    <span class="title">利用規約</span>
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
    /* クリック判定を強制的に行ごとに切り分ける */
.navigation ul li { position: relative; }
.navigation ul li a { position: relative; z-index: 2; }

/* 余計な重なりを潰す */
.navigation ul li { overflow: hidden; }

/* spanがイベント奪うことがあるので無効化（aだけクリック判定にする） */
.navigation ul li a * { pointer-events: none; }

</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toggle = document.querySelector('.nav-toggle');
        const nav = document.querySelector('.navigation');
        if (!toggle || !nav) return;

        const closeMenu = () => {
            document.body.classList.remove('nav-open');
            nav.dataset.open = 'false';
            toggle.setAttribute('aria-expanded', 'false');
        };

        toggle.addEventListener('click', () => {
            const isOpen = document.body.classList.toggle('nav-open');
            nav.dataset.open = isOpen ? 'true' : 'false';
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeMenu();
            }
        });
    });
</script>
