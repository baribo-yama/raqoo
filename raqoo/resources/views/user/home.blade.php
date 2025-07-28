<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Raqoo</title>
  <link rel="stylesheet" href="style.css"/>
  <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>
<body>
  <div class="main-content">
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

    <!-- ヘッダー -->
    <div class="header">
      <div class="logo-box">Raqoo</div>
      <div class="profile-box">
        <div class="profile-icon">
          <svg width="32" height="32" viewBox="0 0 32 32">
            <circle cx="16" cy="12" r="8" fill="#000"/>
            <ellipse cx="16" cy="26" rx="12" ry="6" fill="#000"/>
          </svg>
        </div>
        <div class="profile-detail">
          <div class="profile-id">学籍番号 ○○○○○○</div>
          <div class="profile-name">名前〇〇〇〇</div>
        </div>
      </div>
    </div>

    <!-- 検索バー -->
    <div class="search-container">
      <form class="search-form">
        <div class="form-row">
          <div class="form-group">
            <label>日付</label>
            <input type="date" placeholder="yyyy/mm/dd">
          </div>
          <div class="form-group">
            <label>何限～何限利用するか</label>
            <input type="text" placeholder="例: 1限 〜 3限">
          </div>
          <div class="form-group">
            <label>利用人数</label>
            <input type="text" placeholder="〇人">
          </div>
        </div>
        <div class="form-row-btn">
          <button type="submit" class="search-btn">検索</button>
        </div>
      </form>
    </div>

    <!-- 曜日タブ -->
    <div>
      <div class="day-tabs">
        <div class="day-tab">
          <div class="day-label">日</div>
          <div class="date-label">05/19</div>
        </div>
        <div class="day-tab">
          <div class="day-label">月</div>
          <div class="date-label">05/20</div>
        </div>
        <div class="day-tab">
          <div class="day-label">火</div>
          <div class="date-label">05/21</div>
        </div>
        <div class="day-tab">
          <div class="day-label">水</div>
          <div class="date-label">05/22</div>
        </div>
        <div class="day-tab">
          <div class="day-label">木</div>
          <div class="date-label">05/23</div>
        </div>
        <div class="day-tab">
          <div class="day-label">金</div>
          <div class="date-label">05/24</div>
        </div>
        <div class="day-tab">
          <div class="day-label">土</div>
          <div class="date-label">05/25</div>
        </div>
      </div>
      <div class="date-section">
        <div class="date-title-row">
          <span class="date-year">2025</span>
          <span class="date-main">05/20</span>
          <span class="date-weekday">（月）</span>
        </div>
        <div class="status-bar-2">
          <span class="status-line"></span>
          ･･･現在使用中　
          <span class="status-dot"></span>
          ･･･利用可能　
        </div>
      </div>
    </div>

    <!-- 教室情報のやつ -->
    <div class="room-groups">
      <!-- 501教室 -->
      <div class="room-group">
  <div class="room-title">501教室</div>
  <div class="room-times">
    <div class="time-card unavailable">
      <div class="time-main">09:10</div>
      <div class="time-sub">~10:40</div>
      <div class="floor">5階</div>
      <div class="unavailable-label">― 利用不可</div>
    </div>
    <div class="time-card available">
      <div class="time-main">10:50</div>
      <div class="time-sub">~12:20</div>
      <div class="floor">5階</div>
      <div class="available-label">◯ 利用可能</div>
    </div>
    <div class="time-card unavailable">
      <div class="time-main">13:00</div>
      <div class="time-sub">~14:30</div>
      <div class="floor">5階</div>
      <div class="unavailable-label">― 利用不可</div>
    </div>
    <div class="time-card unavailable">
      <div class="time-main">14:40</div>
      <div class="time-sub">~16:10</div>
      <div class="floor">5階</div>
      <div class="unavailable-label">― 利用不可</div>
    </div>
      <div class="time-card available">
    <div class="time-main">16:20</div>
    <div class="time-sub">~17:50</div>
    <div class="floor">5階</div>
    <div class="available-label">◯ 利用可能</div>
  </div>
  <div class="time-card unavailable">
    <div class="time-main">18:00</div>
    <div class="time-sub">~19:30</div>
    <div class="floor">5階</div>
    <div class="unavailable-label">― 利用不可</div>
  </div>
</div>

      <!-- 502教室 -->
      <div class="room-group">
        <div class="room-title">502教室</div>
        <div class="room-times">
          <div class="time-card unavailable">
            <div class="time-main">09:10</div>
            <div class="time-sub">~10:40</div>
            <div class="floor">5階</div>
            <div class="unavailable-label">― 利用不可</div>
          </div>
             <div class="time-card available">
      <div class="time-main">10:50</div>
      <div class="time-sub">~12:20</div>
      <div class="floor">5階</div>
      <div class="available-label">◯ 利用可能</div>
          </div>
          <div class="time-card unavailable">
            <div class="time-main">13:00</div>
            <div class="time-sub">~14:30</div>
            <div class="floor">5階</div>
            <div class="unavailable-label">― 利用不可</div>
          </div>
          <div class="time-card unavailable">
            <div class="time-main">14:40</div>
            <div class="time-sub">~16:10</div>
            <div class="floor">5階</div>
            <div class="unavailable-label">― 利用不可</div>
            </div>
            <div class="time-card available">
            <div class="time-main">16:20</div>
            <div class="time-sub">~17:50</div>
            <div class="floor">5階</div>
            <div class="available-label">◯ 利用可能</div>
          </div>
          <div class="time-card unavailable">
            <div class="time-main">18:00</div>
            <div class="time-sub">~19:30</div>
            <div class="floor">5階</div>
            <div class="unavailable-label">― 利用不可</div>
          </div>
        </div>
      </div>
      <!-- 503教室 -->
      <div class="room-group">
        <div class="room-title">503教室</div>
        <div class="room-times">
          <div class="time-card unavailable">
            <div class="time-main">09:10</div>
            <div class="time-sub">~10:40</div>
            <div class="floor">5階</div>
            <div class="unavailable-label">― 利用不可</div>
          </div>
          <div class="time-card unavailable">
            <div class="time-main">10:50</div>
            <div class="time-sub">~12:20</div>
            <div class="floor">5階</div>
            <div class="unavailable-label">― 利用不可</div>
          </div>
          <div class="time-card available">
            <div class="time-main">13:00</div>
            <div class="time-sub">~14:30</div>
            <div class="floor">5階</div>
            <div class="available-label">◯ 利用可能</div>
          </div>
          <div class="time-card available">
            <div class="time-main">14:40</div>
            <div class="time-sub">~16:10</div>
            <div class="floor">5階</div>
            <div class="available-label">◯ 利用可能</div>
          </div>
          <div class="time-card available">
            <div class="time-main">16:20</div>
            <div class="time-sub">~17:50</div>
            <div class="floor">5階</div>
            <div class="available-label">◯ 利用可能</div>
          </div>
            <div class="time-card unavailable">
            <div class="time-main">18:00</div>
            <div class="time-sub">~19:30</div>
            <div class="floor">5階</div>
            <div class="unavailable-label">― 利用不可</div>
            </div>  
              </div>
            </div>
          </div>

  <script>
    const list = document.querySelectorAll(".list");
    function activeLink(){
      list.forEach(item => item.classList.remove("active"));
      this.classList.add("active");
    }
    list.forEach(item => item.addEventListener("click", activeLink));
  </script>
  <script src="script.js"></script>
</body>
</html>
