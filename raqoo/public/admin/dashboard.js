// ========== グローバル変数 ==========
let currentDate = new Date();
let selectedDate = new Date();

// サンプルデータ
// 本番はこれいらない
const rooms = ['A101', 'A102', 'A103', 'A104', 'A105'];
const periods = [
  { id: 1, time: '9:00-10:30' },
  { id: 2, time: '10:40-12:10' },
  { id: 3, time: '13:00-14:30' },
  { id: 4, time: '14:40-16:10' },
  { id: 5, time: '16:20-17:50' },
  { id: 6, time: '18:00-19:30' }
];

const reservations = {
  '2025-11-15': {
    'A101': { 1: '基礎プログラミング', 3: '予約済み' },
    'A102': { 2: 'Web開発演習' }
  }
};

const requests = [
  { id: 1, name: '田中太郎', room: 'A103', date: '2025-11-20', period: 3 },
  { id: 2, name: '佐藤花子', room: 'A104', date: '2025-11-21', period: 4 },
  { id: 3, name: '鈴木一郎', room: 'A101', date: '2025-11-22', period: 2 }
];

// ========== 初期化 ==========
// 初回実行
document.addEventListener('DOMContentLoaded', () => {
  renderCalendar();
  renderRoomTable();
  // renderRequestList();
  setupEventListeners();

  updateReservationBadge();  // 初回実行
  // 30秒ごとに更新（定期実行）
  setInterval(updateReservationBadge, 30000);
});

// ========== カレンダー描画 ==========
function renderCalendar() {
  const calendar = document.getElementById('calendar');
  const year = currentDate.getFullYear();
  const month = currentDate.getMonth();

  document.getElementById('currentMonth').textContent =
    `${year}年${month + 1}月`;

  const firstDay = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();

  calendar.innerHTML = '';

  // 曜日ヘッダー
  ['日', '月', '火', '水', '木', '金', '土'].forEach(day => {
    const dayEl = document.createElement('div');
    dayEl.textContent = day;
    dayEl.style.fontWeight = '600';
    dayEl.style.color = '#6b7280';
    dayEl.style.padding = '8px';
    dayEl.style.textAlign = 'center';
    calendar.appendChild(dayEl);
  });

  // 空白セル
  for (let i = 0; i < firstDay; i++) {
    calendar.appendChild(document.createElement('div'));
  }

  // 日付セル
  const today = new Date();
  for (let day = 1; day <= daysInMonth; day++) {
    const dayEl = document.createElement('div');
    dayEl.className = 'calendar-day';
    dayEl.textContent = day;

    const cellDate = new Date(year, month, day);

    if (isSameDay(cellDate, today)) {
      dayEl.classList.add('today');
    }

    if (isSameDay(cellDate, selectedDate)) {
      dayEl.classList.add('selected');
    }

    dayEl.addEventListener('click', () => {
      selectedDate = cellDate;
      renderCalendar();
      renderRoomTable();
    });

    calendar.appendChild(dayEl);
  }
}


// 教室テーブルのセルを取得
function renderRoomTable() {

  const date = formatDate(selectedDate)

  document.getElementById('selectedDate').textContent = formatDateJapanese(selectedDate);

  const cells = document.querySelectorAll('td[data-room-id]');

  cells.forEach(cell => {
    cell.classList.remove('unavailable');
    cell.textContent = '';
    cell.style.backgroundColor = '';
    cell.style.color = '';
  });

  // サーバーからステータスを取得
  fetch(`/admin/room-status?date=${date}`)
    .then(response => response.json())
    .then(data => {
      console.log('取得したステータス:', data);

      cells.forEach(cell => {
        const roomId = cell.dataset.roomId;
        const periodId = cell.dataset.periodId;

        // 該当するステータスを検索
        const statusData = data.find(
          item => item.room_id == roomId && item.period == periodId
        );

        // statusDataが存在する場合
        if (statusData) {
          // if (statusData && statusData.status === 'unavailable') {
          // cell.classList.add('unavailable');
          // cell.textContent = '利用不可';
          // cell.style.backgroundColor = '#e5e7eb';
          // cell.style.color = '#6b7280';
          // }
          if (statusData.status === 'reserved') {
            cell.classList.add('reserved');
            cell.textContent = '予約';
            cell.style.backgroundColor = '#dbeafe';
            cell.style.color = '#1e40af';
            cell.style.fontWeight = '600';
          }
        }
      });
    })
    .catch(error => console.error('ステータス取得エラー:', error));



  // クリックイベントの設定
  cells.forEach(cell => {
    cell.style.cursor = 'pointer';
    cell.onclick = () => {
      toggleCellStatus(cell);
    };
  });

}

function toggleCellStatus(cell) {
  const roomId = cell.dataset.roomId;
  const periodId = cell.dataset.periodId;
  const date = formatDate(selectedDate);

  // モーダルを表示
  showCellDetailModal(roomId, periodId, date, cell)
  console.log('がクリックされました')
}



// ========== 予約リクエスト一覧描画 ==========
// function renderRequestList() {
//   const container = document.getElementById('requestList');
//   container.innerHTML = '';

//   requests.forEach(req => {
//     const card = document.createElement('div');
//     card.className = 'request-card';
//     card.innerHTML = `
//       <div class="request-card-header">
//         <h4>${req.name}</h4>
//       </div>
//       <div class="request-card-info">教室: ${req.room}</div>
//       <div class="request-card-info">日付: ${req.date}</div>
//       <div class="request-card-info">時限: ${req.period}限</div>
//       <div class="request-actions">
//         <button class="btn btn-approve" onclick="approveRequest(${req.id})">承認</button>
//         <button class="btn btn-reject" onclick="rejectRequest(${req.id})">却下</button>
//       </div>
//     `;
//     container.appendChild(card);
//   });
// }

// 予約リクエスト数を更新する
/**
 * 管理画面の予約リクエスト用バッジの件数を最新の状態に更新する。
 *
 * `/admin/reservations/count` エンドポイントから予約リクエスト数を取得し、
 * `#reservationBadge` 要素のテキストと表示／非表示を更新する。
 *
 * 想定されるAPIレスポンス:
 *   { count: number }  // count が未定義または falsy の場合は 0 として扱う
 *
 * この関数はページロード時および一定間隔（例: 30秒ごと）のタイマーから
 * 呼び出されることを想定している。
 */
function updateReservationBadge() {
  // 予約数を取得するAPIエンドポイント
  fetch('/admin/reservations/count')
    .then(response => response.json())
    .then(data => {
      const badge = document.getElementById('reservationBadge');
      if (badge) {
        // data.countがあればtrutyでdata.countが代入, なければfaultyで0になる
        const count = data.count || 0;
        badge.textContent = count;
        // 0件の場合は非表示にする
        // 1件でもあればcssでdisplay = 'block' で表示される
        badge.style.display = count > 0 ? 'block' : 'none';
      }
    })
    .catch(error => console.error('予約取得数エラー:', error));
}


// ========== イベントリスナー設定 ==========
function setupEventListeners() {
  // 月移動
  document.getElementById('prevMonth').addEventListener('click', () => {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar();
  });

  document.getElementById('nextMonth').addEventListener('click', () => {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar();
  });

  // サイドバー開閉
  const sidebar = document.getElementById('requestSidebar');
  const overlay = document.getElementById('overlay');
  const toggleBtn = document.getElementById('requestToggle');
  const closeBtn = document.getElementById('closeSidebar');

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.add('open');
    overlay.classList.add('active');
  });

  closeBtn.addEventListener('click', () => {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
  });

  overlay.addEventListener('click', () => {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
  });

}

// ========== ユーティリティ関数 ==========
function isSameDay(date1, date2) {
  return date1.getDate() === date2.getDate() &&
    date1.getMonth() === date2.getMonth() &&
    date1.getFullYear() === date2.getFullYear();
}

function formatDate(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function formatDateJapanese(date) {
  const days = ['日', '月', '火', '水', '木', '金', '土'];
  const year = date.getFullYear();
  const month = date.getMonth() + 1;
  const day = date.getDate();
  const dayOfWeek = days[date.getDay()];
  return `${year}年${month}月${day}日（${dayOfWeek}）`;
}

function handleSlotClick(room, period, date) {
  const content = prompt(`${room} ${period}限の予約内容を入力してください:`);
  if (content) {
    if (!reservations[date]) reservations[date] = {};
    if (!reservations[date][room]) reservations[date][room] = {};
    reservations[date][room][period] = content;
    renderRoomTable();
  }
}

function approveRequest(id) {
  alert(`リクエスト${id}を承認しました`);
  // ここで実際の処理を実装
}

function rejectRequest(id) {
  alert(`リクエスト${id}を却下しました`);
  // ここで実際の処理を実装
}
