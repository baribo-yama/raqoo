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
      <form method="POST" action="{{ route('logout') }}" class="logout-form">
        @csrf
        <button type="submit" class="logout-btn">
          <span class="icon"><ion-icon name="log-out-outline"></ion-icon></span>
          <span class="title">ログアウト</span>
        </button>
      </form>
    </li>
  </ul>
</div>

<style>
  /* ✅ badgeだけここに残してもOK（home.cssへ移しても可） */
  .notification-badge{
    position:absolute;
    top:-5px;
    right:-5px;
    background:red;
    color:white;
    border-radius:50%;
    width:18px;
    height:18px;
    font-size:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:bold;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const toggles = Array.from(document.querySelectorAll('.nav-toggle'));
  const navs = Array.from(document.querySelectorAll('.navigation'));

  // デバッグ（本番は消してOK）
  // console.log('[sidebar] toggles:', toggles.length, 'navs:', navs.length);

  if (toggles.length === 0 || navs.length === 0) return;

  const setOpen = (open) => {
    document.body.classList.toggle('nav-open', open);
    navs.forEach(n => n.dataset.open = open ? 'true' : 'false');
    toggles.forEach(t => t.setAttribute('aria-expanded', open ? 'true' : 'false'));
  };

  toggles.forEach(t => {
    t.addEventListener('click', (e) => {
      e.preventDefault();
      const open = !document.body.classList.contains('nav-open');
      setOpen(open);
    });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') setOpen(false);
  });
});
</script>
