<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>お知らせ</title>
  <link rel="stylesheet" href="info.css">
  <!-- FontAwesome for main content icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="app-container">
    <!-- サイドバー -->
    <div class="navigation">
      <ul>
        <li class="list active">
          <a href="#">
            <span class="icon"><ion-icon name="home-outline"></ion-icon></span>
            <span class="title">ホーム</span>
          </a>
        </li>
        <li class="list">
          <a href="#">
            <span class="icon"><ion-icon name="person-circle-outline"></ion-icon></span>
            <span class="title">プロフィール</span>
          </a>
        </li>
        <li class="list">
          <a href="#">
            <span class="icon"><ion-icon name="settings-outline"></ion-icon></span>
            <span class="title">設定</span>
          </a>
        </li>
        <li class="list">
          <a href="#">
            <span class="icon"><ion-icon name="radio-outline"></ion-icon></span>
            <span class="title">お知らせ</span>
          </a>
        </li>
        <li class="list">
          <a href="#">
            <span class="icon"><ion-icon name="newspaper-outline"></ion-icon></span>
            <span class="title">利用規約</span>
          </a>
        </li>
        <li class="list">
          <a href="#">
            <span class="icon"><ion-icon name="log-out-outline"></ion-icon></span>
            <span class="title">サインアウト</span>
          </a>
        </li>
      </ul>
    </div>

    <!-- メインパネル -->
    <div class="notice-panel">
      <div class="notice-list">
        <div class="notice-list-header">お知らせ</div>
        <div class="notice-list-item active">
          <div class="notice-avatar"><i class="fas fa-user"></i></div>
          <div class="notice-list-content">
            <div class="notice-list-title">機材庫</div>
            <div class="notice-list-message">教室の予約が<br>完了しました</div>
          </div>
        </div>
        <!-- 他の通知を追加する場合はここに同じ構造で -->
      </div>
      <div class="notice-detail">
        <div class="notice-detail-card">
          <div class="notice-detail-header">
            <i class="fas fa-user"></i>
            <span>機材庫</span>
          </div>
          <div class="notice-detail-message">
            ○○さん予約が完了しました。<br>
            当日の〇〇日〇〇時〇〇分までに機材庫まで本人確認と通知の受け取りに来てください
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Ionicons for sidebar -->
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
  <script src="info.js"></script>
</body>
</html>
