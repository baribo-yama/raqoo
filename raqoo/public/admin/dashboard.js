// ========== グローバル変数 ==========
let currentDate = new Date();
let selectedDate = new Date();

// サンプルデータ
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
document.addEventListener('DOMContentLoaded', () => {
  renderCalendar();
  renderRoomTable();
  renderRequestList();
  setupEventListeners();
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

// ========== 教室テーブル描画 ==========
// function renderRoomTable() {
//   const tbody = document.getElementById('roomTableBody');
//   const dateStr = formatDate(selectedDate);

//   document.getElementById('selectedDate').textContent =
//     formatDateJapanese(selectedDate);

//   tbody.innerHTML = '';

//   rooms.forEach(room => {
//     const tr = document.createElement('tr');

//     // 教室名セル
//     const roomCell = document.createElement('td');
//     roomCell.className = 'room-col';
//     roomCell.textContent = room;
//     tr.appendChild(roomCell);

//     // 各時限セル
//     periods.forEach(period => {
//       const td = document.createElement('td');
//       const slot = document.createElement('div');
//       slot.className = 'time-slot';

//       const reservation = reservations[dateStr]?.[room]?.[period.id];

//       if (reservation) {
//         slot.classList.add('reserved');
//         slot.innerHTML = `<div class="slot-content">${reservation}</div>`;
//       } else {
//         slot.addEventListener('click', () => {
//           handleSlotClick(room, period.id, dateStr);
//         });
//       }

//       td.appendChild(slot);
//       tr.appendChild(td);
//     });

//     tbody.appendChild(tr);
//   });
// }

function clickTable() {
  const
}

// ========== 予約リクエスト一覧描画 ==========
function renderRequestList() {
  const container = document.getElementById('requestList');
  container.innerHTML = '';

  requests.forEach(req => {
    const card = document.createElement('div');
    card.className = 'request-card';
    card.innerHTML = `
      <div class="request-card-header">
        <h4>${req.name}</h4>
      </div>
      <div class="request-card-info">教室: ${req.room}</div>
      <div class="request-card-info">日付: ${req.date}</div>
      <div class="request-card-info">時限: ${req.period}限</div>
      <div class="request-actions">
        <button class="btn btn-approve" onclick="approveRequest(${req.id})">承認</button>
        <button class="btn btn-reject" onclick="rejectRequest(${req.id})">却下</button>
      </div>
    `;
    container.appendChild(card);
  });
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
