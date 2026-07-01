document.addEventListener("DOMContentLoaded", () => {
  // 日付や曜日の表示をそろえるために、曜日の配列と日付変換用の関数を用意する
  const jaWeek = ["日", "月", "火", "水", "木", "金", "土"];
  const pad2 = (n) => String(n).padStart(2, "0");
  const toISO = (d) => `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
  const toMMDD = (d) => `${pad2(d.getMonth() + 1)}/${pad2(d.getDate())}`;

  // 画面幅が560px以下かどうかを調べる関数（スマホ想定）
  const isMobile560 = () => window.matchMedia("(max-width: 560px)").matches;

  // 日付表示の更新で使うHTML要素（日付・曜日・選択中の日付を持つ要素）を取得している
  const mainEl = document.querySelector(".date-main");
  const wdayEl = document.querySelector(".date-weekday");
  const dateInput = document.querySelector("#dateInput");

  // 曜日タブ、戻るボタン、進むボタンのHTML要素を取得する
  const tabs = document.querySelectorAll(".day-tabs .day-tab");
  const prevBtn = document.querySelector(".strip-prev");
  const nextBtn = document.querySelector(".strip-next");

  // 予約状況シートを操作するために、開閉ボタンとシート本体・予約情報の表示場所を取得している
  const openSheetBtn = document.querySelector("#openStatusSheet");
  const closeSheetBtn = document.querySelector("#closeStatusSheet");
  const sheet = document.querySelector("#statusSheet");
  const sheetBody = document.querySelector("#statusSheetBody");

  // sheet-openクラスとaria-hiddenを切り替えて、予約状況シートの開閉状態を管理する
  const openSheet = () => {
    document.body.classList.add("sheet-open");
    sheet?.setAttribute("aria-hidden", "false");
  };
  
  const closeSheet = () => {
    if (sheet?.contains(document.activeElement)) {
      openSheetBtn?.focus();
    }

    document.body.classList.remove("sheet-open");
    sheet?.setAttribute("aria-hidden", "true");
  };
  
  // 予約状態を、予約状況シートで表示する日本語テキストに変換する
  function getReservationStatusText(status) {
    if (status === "pending") {
      return "申請中";
    }

    if (status === "reserved") {
      return "予約済み";
    }

    if (status === "approved") {
      return "予約済み";
    }

    if (status === "in_use") {
      return "利用中";
    }

    if (status === "finished") {
      return "利用完了";
    }

    if (status === "completed") {
      return "完了";
    }

    if (status === "cancelled") {
      return "キャンセル";
    }

    if (status === "rejected") {
      return "却下";
    }

    if (status) {
      return status;
    }

    return "不明";
  }

  // 予約状態を、ホーム画面の時限カードで使う状態クラスに変換する
  function getTimeCardStatus(status) {
    if (status === "reserved" || status === "approved") {
      return "reserved";
    }

    if (status === "pending") {
      return "pending";
    }

    if (status === "in_use") {
      return "in_use";
    }

    return "unavailable";
  }

  // 予約カードで表示する開始時間と終了時間を定義する
  const timeTable = {
    1: ["09:10", "10:40"],
    2: ["10:50", "12:20"],
    3: ["13:00", "14:30"],
    4: ["14:40", "16:10"],
    5: ["16:20", "17:50"],
    6: ["18:00", "19:30"],
  };

  // 指定日の予約情報を取得する前に、予約状況シートへ「読み込み中...」を表示する
  async function renderMyReservationsSheet(dateISO) {
    if (!sheetBody) {
      return;
    }

    //予約情報を取得するまで「読み込み中...」を表示する
    sheetBody.textContent = "読み込み中...";

    try {
      const response = await fetch(`/user/my-reservations?date=${encodeURIComponent(dateISO)}`, { cache: "no-store" });
      const reservations = await response.json();

      const priority = (status) => {
        if (status === "reserved" || status === "approved") {
          return 0;
        }

        if (status === "pending") {
          return 1;
        }

        return 9;
      };

      reservations.sort((firstReservation, secondReservation) => {
        const firstPriority = priority(firstReservation.status);
        const secondPriority = priority(secondReservation.status);

        if (firstPriority !== secondPriority) {
          return firstPriority - secondPriority;
        }

        if (firstReservation.date === secondReservation.date) {
          return (firstReservation.period || 0) - (secondReservation.period || 0);
        }

        return String(firstReservation.date).localeCompare(String(secondReservation.date));
      });

      sheetBody.innerHTML = "";

      if (!reservations.length) {
        const emptyDiv = document.createElement("div");
        emptyDiv.className = "my-empty";
        emptyDiv.textContent = "この日の予約はありません";
        sheetBody.appendChild(emptyDiv);
        return;
      }

      reservations.forEach((reservation) => {
        const reservationStatusText = getReservationStatusText(reservation.status);
        const timeCardStatus = getTimeCardStatus(reservation.status);

        let reservationTimes = ["--:--", "--:--"];
        if (timeTable[reservation.period]) {
          reservationTimes = timeTable[reservation.period];
        }

        let roomNameText = "教室";
        if (reservation.room_name) {
          roomNameText = reservation.room_name;
        }

        let periodNumberText = "-";
        if (reservation.period) {
          periodNumberText = String(reservation.period);
        }

        const card = document.createElement("div");
        const cardClassNames = ["time-card", "sheet-time-card", timeCardStatus];
        card.className = cardClassNames.join(" ");

        const layout = document.createElement("div");
        layout.className = "sp-layout";

        const periodBox = document.createElement("div");
        periodBox.className = "sp-period-box";

        const periodNumber = document.createElement("span");
        periodNumber.className = "p-num";
        periodNumber.textContent = periodNumberText;

        const periodText = document.createElement("span");
        periodText.className = "p-text";
        periodText.textContent = "限";

        periodBox.appendChild(periodNumber);
        periodBox.appendChild(periodText);

        const infoBox = document.createElement("div");
        infoBox.className = "sp-info-box";

        const timeRow = document.createElement("div");
        timeRow.className = "sp-time-row";

        const startTime = document.createElement("span");
        startTime.className = "time-main";
        startTime.textContent = reservationTimes[0];

        const tilde = document.createElement("span");
        tilde.className = "tilde";
        tilde.textContent = "~";

        const endTime = document.createElement("span");
        endTime.className = "time-sub";
        endTime.textContent = reservationTimes[1];

        timeRow.appendChild(startTime);
        timeRow.appendChild(tilde);
        timeRow.appendChild(endTime);

        const statusArea = document.createElement("div");
        statusArea.className = "sp-status-area";

        const statusLabel = document.createElement("span");
        statusLabel.className = "unavailable-label";
        statusLabel.textContent = roomNameText + "・" + reservationStatusText;

        statusArea.appendChild(statusLabel);

        infoBox.appendChild(timeRow);
        infoBox.appendChild(statusArea);

        const actionBox = document.createElement("div");
        actionBox.className = "sp-action-box";

        const detailLink = document.createElement("a");
        detailLink.className = "sp-reserve-btn sheet-detail-link";
        detailLink.textContent = "詳細を見る";

        if (reservation.detail_url) {
          detailLink.href = reservation.detail_url;
        } else {
          detailLink.href = "#";
          detailLink.setAttribute("aria-disabled", "true");
          detailLink.setAttribute("tabindex", "-1");
          detailLink.addEventListener("click", (event) => event.preventDefault());
        }

        detailLink.setAttribute(
          "aria-label",
          roomNameText + " " + periodNumberText + "限の詳細を見る"
        );

        actionBox.appendChild(detailLink);

        layout.appendChild(periodBox);
        layout.appendChild(infoBox);
        layout.appendChild(actionBox);

        card.appendChild(layout);
        sheetBody.appendChild(card);
      });

    } catch (e) {
      console.error(e);
      sheetBody.innerHTML = "";
      const errDiv = document.createElement("div");
      errDiv.className = "my-empty";
      errDiv.textContent = "取得に失敗しました";
      sheetBody.appendChild(errDiv);
    }
  }

  // ---------- シート開閉（スマホ時のみ有効） ----------
  if (isMobile560()) {
    // 「予約状況」ボタン押下で、その日付の自分の予約を読み込み→シートを開く
    openSheetBtn?.addEventListener("click", () => {
      const iso = dateInput?.value ? dateInput.value : toISO(new Date());
      renderMyReservationsSheet(iso);
      openSheet();
    });

    // シートの閉じるボタン
    closeSheetBtn?.addEventListener("click", closeSheet);
  }

  // --- 日付の初期化 ---
  let selectedDate;
  if (dateInput && dateInput.value) {
    // hidden/inputの値（YYYY-MM-DD）がある場合はその日付を初期表示にする
    const [year, month, day] = dateInput.value.split("-").map(Number);
    selectedDate = new Date(year, month - 1, day);
  } else {
    // 値が無い場合は今日を初期表示にする
    selectedDate = new Date();
  }
  // ストリップは「週の先頭（日曜）」から7日を描画するため、選択日から週頭を算出
  let startDate = new Date(selectedDate.getTime() - selectedDate.getDay() * 86400000);

  // 中央の日付表示・hidden input・空き状況表示を、指定Dateに合わせて更新する
  function updateCenterDisplay(d) {
    if (mainEl) mainEl.textContent = toMMDD(d);
    if (wdayEl) wdayEl.textContent = `（${jaWeek[d.getDay()]}）`;
    if (dateInput) dateInput.value = toISO(d);

    updateRoomStatuses(toISO(d));

    if (document.body.classList.contains("sheet-open") && isMobile560()) {
      renderMyReservationsSheet(toISO(d));
    }
  }

  // 指定日(date)の教室×時限ステータスを取得し、各カードの表示/リンク/ボタン状態へ反映する
  function updateRoomStatuses(date) {
    // まずUIを「読み込み中」に寄せる（前日/前回の表示が残らないように初期化）
    document.querySelectorAll('.room-group').forEach((roomGroup) => {
      roomGroup.querySelectorAll('.time-card').forEach((card) => {
        // ★Copilot指摘対応：初期化時に locked クラスも確実に削除する
        card.classList.remove('available', 'pending', 'reserved', 'in_use', 'unavailable', 'locked');

        const availableLabel = card.querySelector(".available-label");
        const unavailableLabel = card.querySelector(".unavailable-label");
        const reserveBtn = card.querySelector(".reserve-btn");

        // Tailwindクラスを使って非表示に切り替え
        if (availableLabel) availableLabel.classList.add('hidden');
        if (unavailableLabel) {
          unavailableLabel.classList.remove('hidden');
          unavailableLabel.textContent = '読み込み中...';
        }
        if (reserveBtn) reserveBtn.classList.add('hidden');

        // 予約ボタン/カードのリンクを「選択日」に合わせて更新する
        // ★Copilot指摘対応：正規表現ではなくURL/URLSearchParamsを使った堅牢なURL書き換え
        if (reserveBtn) {
          let href = reserveBtn.getAttribute('href') || reserveBtn.getAttribute('data-href') || card.dataset.baseHref;

          if (href && typeof href === 'string') {
            try {
              // 相対パスを解析できるよう、ダミーのベースURL（location.origin）を付与
              const url = new URL(href, window.location.origin);

              // パターン1: ?next=... の中にエンコードされて date が含まれている場合
              // (例: /user/precautions?next=/user/reservations/create?date=...)
              if (url.searchParams.has('next')) {
                const nextParam = url.searchParams.get('next');
                const nextUrl = new URL(nextParam, window.location.origin);

                if (nextUrl.searchParams.has('date')) {
                  nextUrl.searchParams.set('date', date);
                  // pathname + search で相対パスに戻してセット
                  url.searchParams.set('next', nextUrl.pathname + nextUrl.search);
                }
              }
              // パターン2: URL自体が直接 ?date=... を持っている場合
              else if (url.searchParams.has('date')) {
                url.searchParams.set('date', date);
              }

              // 元の href が 'http' で始まっていなければ相対パスに戻す
              href = href.startsWith('http') ? url.href : url.pathname + url.search;

            } catch (error) {
              console.warn("URLパースエラー。フォールバックとして正規表現を使用します", error);
              // パースに失敗した時のためのフォールバック
              href = href.replace(/date%3D\d{4}-\d{2}-\d{2}/g, `date%3D${date}`);
              href = href.replace(/date=\d{4}-\d{2}-\d{2}/g, `date=${date}`);
            }

            if (reserveBtn.hasAttribute('href')) reserveBtn.setAttribute("href", href);
            if (reserveBtn.hasAttribute('data-href')) reserveBtn.setAttribute("data-href", href);

            card.dataset.baseHref = href;
            if (card.hasAttribute('href')) {
              card.setAttribute("href", href);
            }
          }
        }
      });
    });

    // 指定日のステータスをAPIから取得して、room_id×periodにマッチするカードへ反映
    fetch(`/user/room-status?date=${date}`, { cache: "no-store" })
      .then((response) => response.json())
      .then((data) => {
        document.querySelectorAll(".room-group").forEach((roomGroup) => {
          const roomId = roomGroup.dataset.roomId;

          roomGroup.querySelectorAll(".time-card").forEach((card) => {
            const period = Number(card.dataset.period);
            // API結果から「この教室・この時限」のレコードを探す（無ければ available 扱い）
            const statusData = data.find((item) => item.room_id == roomId && Number(item.period) === period);
            const status = statusData ? statusData.status : "available";

            card.classList.remove('available', 'pending', 'reserved', 'in_use', 'unavailable', 'locked');
            card.classList.add(status);

            // PC用の要素群
            const availableLabel = card.querySelector(".pc-layout .available-label");
            const unavailableLabel = card.querySelector(".pc-layout .unavailable-label");
            const reserveBtn = card.querySelector(".reserve-btn.pc-only");

            // スマホ用の要素群
            const spAvailableLabel = card.querySelector(".sp-layout .available-label");
            const spUnavailableLabel = card.querySelector(".sp-layout .unavailable-label");
            const spReserveBtn = card.querySelector(".sp-reserve-btn");

            if (status === 'available') {
              // 利用可能：リンク有効・ボタン表示・「空き」ラベル表示
              card.setAttribute("href", card.dataset.baseHref);
              card.classList.remove('locked');
              // ★ Copilot指摘対応: 利用可能な時は aria-disabled を外す
              card.removeAttribute("aria-disabled");

              // PC表示
              if (availableLabel) availableLabel.classList.remove("hidden");
              if (unavailableLabel) unavailableLabel.classList.add("hidden");
              if (reserveBtn) reserveBtn.classList.remove("hidden");

              // スマホ表示
              if (spAvailableLabel) spAvailableLabel.classList.remove("hidden");
              if (spUnavailableLabel) spUnavailableLabel.classList.add("hidden");
              if (spReserveBtn) spReserveBtn.classList.remove("hidden");

            } else {
              // 利用不可：リンク無効・ボタン非表示・理由（授業名 or ステータス）を表示
              card.removeAttribute("href");
              card.classList.add('locked');
              // ★ Copilot指摘対応: 利用不可の時は aria-disabled をつける
              card.setAttribute("aria-disabled", "true");

              const statusLabels = {
                'pending': '申請中',
                'reserved': '予約済み',
                'in_use': '利用中',
                'unavailable': '利用不可',
                'conflict': '同時刻に予約あり',
              };

              const label = statusData?.lesson_title ?? statusLabels[status] ?? '利用不可';

              // PC表示
              if (availableLabel) availableLabel.classList.add("hidden");
              if (unavailableLabel) {
                unavailableLabel.classList.remove("hidden");
                unavailableLabel.textContent = label;
              }
              if (reserveBtn) reserveBtn.classList.add("hidden");

              // スマホ表示
              if (spAvailableLabel) spAvailableLabel.classList.add("hidden");
              if (spUnavailableLabel) {
                spUnavailableLabel.classList.remove("hidden");
                spUnavailableLabel.textContent = label;
              }
              if (spReserveBtn) spReserveBtn.classList.add("hidden");
            }
          });
        });
      })
      .catch((error) => console.error("ステータス取得エラー:", error));
  }

  // 週表示ストリップ（曜日/日付ラベルとactive状態）を、startDate〜7日分で描画する
  function renderStrip() {
    tabs.forEach((tab, i) => {
      const d = new Date(startDate.getTime() + i * 86400000);
      tab.querySelector(".day-label").textContent = jaWeek[d.getDay()];
      tab.querySelector(".date-label").textContent = toMMDD(d);
      tab.dataset.date = toISO(d);

      const active = d.getFullYear() === selectedDate.getFullYear() &&
        d.getMonth() === selectedDate.getMonth() &&
        d.getDate() === selectedDate.getDate();
      tab.classList.toggle("active", active);
    });
  }

  // 初期表示：中央の日付＋空き状況を反映し、ストリップ（1週間）を描画
  updateCenterDisplay(selectedDate);
  renderStrip();

  // ストリップの各日付タブ：クリックした日を選択日として反映
  tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      const iso = tab.dataset.date;
      if (!iso) return;
      const [year, month, day] = iso.split("-").map(Number);
      selectedDate = new Date(year, month - 1, day);
      updateCenterDisplay(selectedDate);
      // 選択日が変わったら、その週の日曜を再計算してストリップを描き直す
      startDate = new Date(selectedDate.getTime() - selectedDate.getDay() * 86400000);
      renderStrip();
    });
  });

  // 前日へ移動：選択日とストリップ開始日を1日戻して反映
  prevBtn?.addEventListener("click", () => {
    selectedDate = new Date(selectedDate.getTime() - 86400000);
    startDate = new Date(startDate.getTime() - 86400000);
    updateCenterDisplay(selectedDate);
    renderStrip();
  });

  // 翌日へ移動：選択日とストリップ開始日を1日進めて反映
  nextBtn?.addEventListener("click", () => {
    selectedDate = new Date(selectedDate.getTime() + 86400000);
    startDate = new Date(startDate.getTime() + 86400000);
    updateCenterDisplay(selectedDate);
    renderStrip();
  });

  // 日付入力（カレンダー等）変更：選択日を更新し、週ストリップと空き状況を再描画
  dateInput?.addEventListener("change", () => {
    const [year, month, day] = dateInput.value.split("-").map(Number);
    const d = new Date(year, month - 1, day);
    if (isNaN(d)) return;
    selectedDate = d;
    updateCenterDisplay(selectedDate);
    startDate = new Date(selectedDate.getTime() - selectedDate.getDay() * 86400000);
    renderStrip();
  });

});
