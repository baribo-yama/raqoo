@props(['unreadCount' => 0])

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
        <span class="icon notification-icon">
          <ion-icon name="notifications-outline"></ion-icon>
          @if ($unreadCount > 0)
            <span class="notification-badge">!</span>
          @endif
        </span>
        <span class="title">お知らせ</span>
      </a>
    </li>

    <li class="list">
      <form method="POST" action="{{ route('logout') }}" class="sidebar-logout-form">
        @csrf
        <button type="submit" class="logout-btn">
          <span class="icon"><ion-icon name="log-out-outline"></ion-icon></span>
          <span class="title">ログアウト</span>
        </button>
      </form>
    </li>
  </ul>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('.nav-toggle');
  const nav = document.querySelector('.navigation');
  const backdrop = document.querySelector('#navBackdrop'); // あれば使う

  if (!toggle || !nav) return;

  const isTabletOrMobile = () =>
    window.matchMedia('(max-width: 1024px)').matches;

  const openMenu = () => {
    document.body.classList.add('nav-open');
    nav.dataset.open = 'true';
    toggle.setAttribute('aria-expanded', 'true');
    backdrop?.setAttribute('aria-hidden', 'false');
  };

  const closeMenu = () => {
    document.body.classList.remove('nav-open');
    nav.dataset.open = 'false';
    toggle.setAttribute('aria-expanded', 'false');
    backdrop?.setAttribute('aria-hidden', 'true');
  };

  // ハンバーガークリック
  toggle.addEventListener('click', (e) => {
    if (!isTabletOrMobile()) return;
    e.stopPropagation();
    document.body.classList.contains('nav-open') ? closeMenu() : openMenu();
  });

  // ★サイドバー外タップで閉じる（最重要）
  document.addEventListener('click', (e) => {
    if (!isTabletOrMobile()) return;
    if (!document.body.classList.contains('nav-open')) return;

    if (!nav.contains(e.target) && !toggle.contains(e.target)) {
      closeMenu();
    }
  });

  // nav内クリックは閉じない
  nav.addEventListener('click', (e) => e.stopPropagation());

  // backdrop があるページでは backdrop でも閉じる
  backdrop?.addEventListener('click', closeMenu);

  // ESC
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeMenu();
  });

  // PC幅に戻ったら閉じる
  window.addEventListener('resize', () => {
    if (!isTabletOrMobile()) closeMenu();
  });
});
</script>
