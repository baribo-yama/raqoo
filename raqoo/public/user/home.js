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
    // --- ステータス表示のリセット処理 ---
    // APIレスポンスを待つ間、ローディング状態にする
    document.querySelectorAll('.room-group').forEach((roomGroup) => {
      roomGroup.querySelectorAll('.time-card').forEach((card) => {
        // ステータスクラスはAPIレスポンス後に設定するため、ここでは削除のみ
        card.classList.remove('available', 'pending', 'reserved', 'in_use', 'unavailable');

        const availableLabel = card.querySelector('.available-label');
        const unavailableLabel = card.querySelector('.unavailable-label');
        const reserveBtn = card.querySelector('.reserve-btn');

        // ローディング中の表示
        if (availableLabel) {
          availableLabel.style.display = 'none';
        }
        if (unavailableLabel) {
          unavailableLabel.style.display = '';
          unavailableLabel.textContent = '読み込み中...';
        }
        if (reserveBtn) {
          reserveBtn.style.display = 'none';
        }

        // 予約ボタンのリンク先（href）に含まれる日付を、新しい日付に書き換える
        if (reserveBtn) {
          let href = reserveBtn.getAttribute('href');
          if (href) {
            // URLエンコードされた日付 (date%3DYYYY-MM-DD) と 通常の日付 (date=YYYY-MM-DD) の両方を置換
            href = href.replace(/date%3D\d{4}-\d{2}-\d{2}/g, `date%3D${date}`);
            href = href.replace(/date=\d{4}-\d{2}-\d{2}/g, `date=${date}`);
            reserveBtn.setAttribute('href', href);
          }
        }
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
            // statusDataの中にlesson_titleがあるか確認デバッグログ
            console.log('statusData', statusData)
            console.log('lesson_title', statusData?.lesson_title)

            const status = statusData ? statusData.status : 'available';

            // デバッグ用ログ
            console.log(`Room ${roomId}, Period ${period}: status = "${status}"`);

            // ステータス別のCSSクラスを設定（target-periodは保持）
            card.classList.remove('available', 'pending', 'reserved', 'in_use', 'unavailable');
            card.classList.add(status);

            const availableLabel = card.querySelector('.available-label');
            const unavailableLabel = card.querySelector('.unavailable-label');
            const reserveBtn = card.querySelector('.reserve-btn');

            if (status === 'available') {
              // 利用可能：予約ボタン表示
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
              // 利用不可：ステータスラベル表示
              if (availableLabel) {
                availableLabel.style.display = 'none';
              }
              if (unavailableLabel) {
                unavailableLabel.style.display = '';
                // ステータスに応じたラベルテキスト
                const statusLabels = {
                  'pending': '申請中',
                  'reserved': '予約済み',
                  'in_use': '利用中',
                  'unavailable': '利用不可',
                  // TODO:授業が入ってたら授業名表示
                };

                // 授業が入っていたら最優先で表示
                const label = statusData?.lesson_title ?? statusLabels[status] ?? '利用不可';
                unavailableLabel.textContent = label;
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
