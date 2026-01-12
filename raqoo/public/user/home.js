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

  // 日付
  let selectedDate = new Date();
  let startDate = new Date(selectedDate.getTime() - selectedDate.getDay() * 86400000);

  // 中央の日付更新
  function updateCenterDisplay(d) {
    mainEl.textContent = toMMDD(d);
    wdayEl.textContent = `（${jaWeek[d.getDay()]}）`;
    if (dateInput) dateInput.value = toISO(d);

    // 選択日付のステータスを取得してカードを更新
    updateRoomStatuses(toISO(d));

    // 予約ボタンのリンクを更新
    updateReserveButtonLinks(toISO(d));
  }

  // 予約ボタンのリンクを更新する関数
  function updateReserveButtonLinks(date) {
    document.querySelectorAll('.reserve-btn').forEach(btn => {
      const roomId = btn.dataset.roomId;
      const period = btn.dataset.period;

      if (roomId && period) {
        // 新しいnextパスを構築
        const nextPath = `/reservations/create?room_id=${roomId}&date=${date}&period=${period}`;
        const newHref = `/precautions?next=${encodeURIComponent(nextPath)}`;
        btn.setAttribute('href', newHref);
      } else {
        // データ属性がない場合は、既存のhrefからnextパラメータを抽出して更新
        const currentHref = btn.getAttribute('href');
        if (!currentHref) return;

        try {
          const url = new URL(currentHref, window.location.origin);
          const nextParam = url.searchParams.get('next');

          if (nextParam) {
            // nextパラメータのURLを解析
            const nextUrl = new URL(nextParam, window.location.origin);

            // dateパラメータを更新
            nextUrl.searchParams.set('date', date);

            // 新しいnextパラメータでURLを再構築
            url.searchParams.set('next', nextUrl.pathname + nextUrl.search);
            btn.setAttribute('href', url.pathname + url.search);
          }
        } catch (e) {
          console.error('予約ボタンリンク更新エラー:', e);
        }
      }
    });
  }

  // 教室ステータスをサーバーから取得して更新
  function updateRoomStatuses(date) {
    fetch(`/user/room-status?date=${date}`)
      .then(response => response.json())
      .then(data => {
        // 各教室カードを更新
        document.querySelectorAll('.room-group').forEach((roomGroup, roomIndex) => {
          const roomId = roomGroup.dataset.roomId;

          roomGroup.querySelectorAll('.time-card').forEach((card, periodIndex) => {
            const period = periodIndex + 1;

            // 該当するステータスを検索
            const statusData = data.find(
              item => item.room_id == roomId && item.period == period
            );

            const status = statusData ? statusData.status : 'available';

            // クラス名とラベルを更新
            card.className = status === 'available' ? 'time-card available' : 'time-card unavailable';

            // ラベルを個別に取得（Bladeで同時に存在する）
            const availableLabel = card.querySelector('.available-label');
            const unavailableLabel = card.querySelector('.unavailable-label');
            const reserveBtn = card.querySelector('.reserve-btn');

            if (status === 'available') {
              // 利用可能な場合
              if (availableLabel) {
                // 利用可能ラベルを表示
                availableLabel.style.display = '';
                availableLabel.textContent = '利用可能';
              }

              if (unavailableLabel) {
                // 利用不可ラベルを非表示
                unavailableLabel.style.display = 'none';
              }
              if (reserveBtn) {
                // 予約ボタンを表示
                reserveBtn.style.display = '';
              }
            } else {
              // 利用不可な場合（pending, reserved, in_use, unavailableすべて）
              if (availableLabel) {
                // 利用可能ラベルを非表示
                availableLabel.style.display = 'none';
              }
              if (unavailableLabel) {
                // 利用不可、そのほかラベルをステータスによって表示
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

      // ローカル日付として正しくパース
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
    // "YYYY-MM-DD" をローカル日付としてパース
    const [year, month, day] = dateInput.value.split('-').map(Number);
    const d = new Date(year, month - 1, day);

    if (isNaN(d)) return;

    selectedDate = d;
    updateCenterDisplay(selectedDate);

    startDate = new Date(selectedDate.getTime() - selectedDate.getDay() * 86400000);

    renderStrip();
  });


});
