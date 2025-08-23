<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>ユーザー情報</title>
 <link rel="stylesheet" href="{{ asset('user/user-date.css') }}"/>
  <!-- Ionicons（サイドバーアイコン） -->
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>
  <div class="main-content">
    <!-- ▼ サイドバー（ホームと同じ構造） -->
    <nav class="navigation">
      <ul>
        <li class="list"><a href="#"><span class="icon"><ion-icon name="home-outline"></ion-icon></span><span class="title">ホーム</span></a></li>
        <li class="list active"><a href="#"><span class="icon"><ion-icon name="person-circle-outline"></ion-icon></span><span class="title">プロフィール</span></a></li>
        <li class="list"><a href="#"><span class="icon"><ion-icon name="settings-outline"></ion-icon></span><span class="title">設定</span></a></li>
        <li class="list"><a href="#"><span class="icon"><ion-icon name="radio-outline"></ion-icon></span><span class="title">お知らせ</span></a></li>
        <li class="list"><a href="#"><span class="icon"><ion-icon name="newspaper-outline"></ion-icon></span><span class="title">利用規約</span></a></li>
        <li class="list"><a href="#"><span class="icon"><ion-icon name="log-out-outline"></ion-icon></span><span class="title">サインアウト</span></a></li>
      </ul>
    </nav>

    <!-- ▼ 右側：中身 -->
    <main class="ui-main">
      <h1 class="ui-title">ユーザー情報</h1>

      <section class="ui-grid">
        <!-- 左：基本情報 -->
        <article class="ui-panel">
          <header class="ui-panel-head">
            <h2>基本情報</h2>
            <button class="ui-edit-btn" type="button" aria-label="基本情報を編集">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 20h9" /><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
              </svg>
              <span>編集</span>
            </button>
          </header>
          <div class="ui-panel-body">
            <dl class="ui-kv">
              <div class="ui-kv-row"><dt>アカウント名</dt><dd>user001</dd></div>
              <div class="ui-kv-row"><dt>所属学校</dt><dd>—</dd></div>
              <div class="ui-kv-row"><dt>学校のメールアドレス</dt><dd>—</dd></div>
            </dl>
          </div>
        </article>

        <!-- 右：借りた教室履歴（枠内“縦スクロール”） -->
        <article class="ui-panel history-panel">
          <header class="ui-panel-head">
            <h2>借りた教室履歴</h2>
          </header>
          <div class="ui-panel-body">
            <!-- ★ このラッパーがスクロール領域 -->
            <div class="ui-table-wrap">
              <table class="ui-table">
                <thead>
                  <tr><th>借りた日</th><th>教室</th><th>時間帯</th></tr>
                </thead>
                <tbody>
                  <!-- 行が増えても枠内だけ縦スクロールになります -->
                  <tr><td>2025-05-18</td><td>601 教室</td><td>17:00–18:30</td></tr>
                  <tr><td>2025-05-17</td><td>210 教室</td><td>15:00–16:30</td></tr>
                  <tr><td>2025-05-16</td><td>105 教室</td><td>13:00–14:30</td></tr>
                  <tr><td>2025-05-15</td><td>502 教室</td><td>10:50–12:20</td></tr>
                  <tr><td>2025-05-14</td><td>402 教室</td><td>9:10–10:40</td></tr>
                  <tr><td>2025-05-13</td><td>601 教室</td><td>17:00–18:30</td></tr>
                  <tr><td>2025-05-12</td><td>210 教室</td><td>15:00–16:30</td></tr>
                  <tr><td>2025-05-11</td><td>105 教室</td><td>13:00–14:30</td></tr>
                  <tr><td>2025-05-10</td><td>301 教室</td><td>11:00–12:30</td></tr>
                  <tr><td>2025-05-09</td><td>502 教室</td><td>9:10–10:40</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </article>
      </section>
    </main>
  </div>
</body>
</html>
