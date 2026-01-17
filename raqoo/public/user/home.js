document.addEventListener("DOMContentLoaded", () => {

  const jaWeek = ["日", "月", "火", "水", "木", "金", "土"];
  const pad2 = (n) => String(n).padStart(2, "0");
  const toISO = (d) => `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
  const toMMDD = (d) => `${pad2(d.getMonth() + 1)}/${pad2(d.getDate())}`;

  // DOM
  const mainEl = document.querySelector(".date-main");
  const wdayEl = document.querySelector(".date-weekday");
  const dateInput = document.querySelector("#dateInput");

  const tabs = document.querySelectorAll(".day-tabs .day-tab");
  const prevBtn = document.querySelector(".strip-prev");
  const nextBtn = document.querySelector(".strip-next");

  // --- 日付の初期化 ---
  let selectedDate;

  if (dateInput && dateInput.value) {
    const [year, month, day] = dateInput.value.split('-').map(Number);
    selectedDate = new Date(year, month - 1, day);
  } else {
    selectedDate = new Date();
  }

  let startDate = new Date(selectedDate.getTime() - selectedDate.getDay() * 86400000);

  // 中央の日付更新
  function updateCenterDisplay(d) {
    mainEl.textContent = toMMDD(d);
    wdayEl.textContent = `（${jaWeek[d.getDay()]}）`;
    if (dateInput) dateInput.value = toISO(d);
    updateRoomStatuses(toISO(d));
  }

  // 教室ステータスをサーバーから取得して更新
  function updateRoomStatuses(date) {
    // --- Copilot修正反映：インデントの修正 ---
    // 通信待ちの間、一旦すべて「利用可能」表示にリセットする
    document.querySelectorAll('.room-group').forEach((roomGroup) => {
      roomGroup.querySelectorAll('.time-card').forEach((card) => {
        card.className = 'time-card available';
        const availableLabel = card.querySelector('.available-label');
        const unavailableLabel = card.querySelector('.unavailable-label');
        const reserveBtn = card.querySelector('.reserve-btn');

        if (availableLabel) {
          availableLabel.style.display = '';
          availableLabel.textContent = '利用可能';
        }
        if (unavailableLabel) unavailableLabel.style.display = 'none';
        if (reserveBtn) reserveBtn.style.display = '';
      });
    });
    // --- リセット処理ここまで ---

    fetch(`/user/room-status?date=${date}`)
      .then(response => response.json())
      .then(data => {
        document.querySelectorAll('.room-group').forEach((roomGroup, roomIndex) => {
          const roomId = roomGroup.dataset.roomId;

          roomGroup.querySelectorAll('.time-card').forEach((card, periodIndex) => {
            const period = periodIndex + 1;

            const statusData = data.find(
              item => item.room_id == roomId && item.period == period
            );

            const status = statusData ? statusData.status : 'available';

            card.className = status === 'available' ? 'time-card available' : 'time-card unavailable';

            const availableLabel = card.querySelector('.available-label');
            const unavailableLabel = card.querySelector('.unavailable-label');
            const reserveBtn = card.querySelector('.reserve-btn');

            if (status === 'available') {
              if (availableLabel) {
                availableLabel.style.display = '';
                availableLabel.textContent = '利用可能';
              }
              if (unavailableLabel) {
                unavailableLabel.style.display = 'none';
              }
              if (reserveBtn) {
                reserveBtn.style.display = '';
              }
            } else {
              if (availableLabel) {
                availableLabel.style.display = 'none';
              }
              if (unavailableLabel) {
                unavailableLabel.style.display = '';
                unavailableLabel.textContent =
                  status === 'pending' ? '申請中' :
                  status === 'in_use' ? '利用中' :
                  status === 'reserved' ? '予約済み' : '利用不可';
              }
              if (reserveBtn) {
                reserveBtn.style.display = 'none';
              }
            }

          });
        });
      })
      .catch(error => console.error('ステータス取得エラー:', error));
  }

  // 7つのタブ表示
  function renderStrip() {
    tabs.forEach((tab, i) => {
      const d = new Date(startDate.getTime() + i * 86400000);

      tab.querySelector(".day-label").textContent = jaWeek[d.getDay()];
      tab.querySelector(".date-label").textContent = toMMDD(d);

      tab.dataset.date = toISO(d);

      const active =
        d.getFullYear() === selectedDate.getFullYear() &&
        d.getMonth() === selectedDate.getMonth() &&
        d.getDate() === selectedDate.getDate();
      tab.classList.toggle("active", active);
    });
  }

  // 初期描画
  updateCenterDisplay(selectedDate);
  renderStrip();

  // タブクリックで中央更新
  tabs.forEach(tab => {
    tab.addEventListener("click", () => {
      const iso = tab.dataset.date;
      if (!iso) return;

      const [year, month, day] = iso.split("-").map(Number);
      selectedDate = new Date(year, month - 1, day);

      updateCenterDisplay(selectedDate);
      startDate = new Date(selectedDate.getTime() - selectedDate.getDay() * 86400000);
      renderStrip();
    });
  });

  // 前へ
  prevBtn?.addEventListener("click", () => {
    selectedDate = new Date(selectedDate.getTime() - 86400000);
    startDate = new Date(startDate.getTime() - 86400000);

    updateCenterDisplay(selectedDate);
    renderStrip();
  });

  // 次へ
  nextBtn?.addEventListener("click", () => {
    selectedDate = new Date(selectedDate.getTime() + 86400000);
    startDate = new Date(startDate.getTime() + 86400000);

    updateCenterDisplay(selectedDate);
    renderStrip();
  });

  // 入力フォーム（日付変更）
  dateInput?.addEventListener("change", () => {
    const [year, month, day] = dateInput.value.split('-').map(Number);
    const d = new Date(year, month - 1, day);

    if (isNaN(d)) return;

    selectedDate = d;
    updateCenterDisplay(selectedDate);
    startDate = new Date(selectedDate.getTime() - selectedDate.getDay() * 86400000);
    renderStrip();
  });

});