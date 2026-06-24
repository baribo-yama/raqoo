document.addEventListener("DOMContentLoaded", () => {
  // 日付や曜日の表示をそろえるために、曜日の配列と日付変換用の関数が用意する
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

  // // sheet-openクラスとaria-hiddenを切り替えて、予約状況シートの開閉状態を管理する
  const openSheet = () => {
    document.body.classList.add("sheet-open");
    sheet?.setAttribute("aria-hidden", "false");
  };　
  
  const closeSheet = () => {
    document.body.classList.remove("sheet-open");
    sheet?.setAttribute("aria-hidden", "true");
  };

  // ---------- ステータス表示（日本語＆色用class） ----------
  // サーバーのstatus文字列を、表示文言とバッジ用classに変換する
  function statusMeta(status) {
    switch (status) {
      case "pending": return { text: "申請中", cls: "my-badge pending" };
      case "reserved": return { text: "予約済み", cls: "my-badge reserved" };
      case "in_use": return { text: "利用中", cls: "my-badge in_use" };
      case "finished": return { text: "利用完了", cls: "my-badge completed" };
      case "cancelled": return { text: "キャンセル", cls: "my-badge cancelled" };
      case "rejected": return { text: "却下", cls: "my-badge rejected" };
      case "approved": return { text: "予約済み", cls: "my-badge reserved" };
      case "completed": return { text: "完了", cls: "my-badge completed" };
      default: return { text: status || "不明", cls: "my-badge other" };
    }
  }

  const timeTable = {
    1: ["09:10", "10:40"],
    2: ["10:50", "12:20"],
    3: ["13:00", "14:30"],
    4: ["14:40", "16:10"],
    5: ["16:20", "17:50"],
    6: ["18:00", "19:30"],
  };

  // ---------- 自分の予約を取得してシート描画 ----------
  // 指定日(dateISO)の自分の予約一覧を取得し、スマホ用シートにカードとして描画する
  async function renderMyReservationsSheet(dateISO) {
    if (!sheetBody) return;

    sheetBody.textContent = "読み込み中...";

    try {
      const res = await fetch(`/user/my-reservations?date=${encodeURIComponent(dateISO)}`, { cache: "no-store" });
      const rows = await res.json();

      const priority = (st) => {
        if (st === "reserved" || st === "approved") return 0;
        if (st === "pending") return 1;
        return 9;
      };

      rows.sort((a, b) => {
        const pa = priority(a.status);
        const pb = priority(b.status);
        if (pa !== pb) return pa - pb;
        if (a.date === b.date) return (a.period || 0) - (b.period || 0);
        return String(a.date).localeCompare(String(b.date));
      });

      sheetBody.innerHTML = "";

      if (!rows.length) {
        const emptyDiv = document.createElement("div");
        emptyDiv.className = "my-empty";
        emptyDiv.textContent = "この日の予約はありません";
        sheetBody.appendChild(emptyDiv);
        return;
      }

      rows.forEach((r) => {
        const meta = statusMeta(r.status);
        const times = timeTable[r.period] || ["--:--", "--:--"];
        const isApproved = (r.status === "reserved" || r.status === "approved");

        const card = document.createElement("div");
        card.className = `my-card ${isApproved ? "is-approved" : ""}`;

        const top = document.createElement("div");
        top.className = "my-top";

        const roomName = document.createElement("div");
        roomName.className = "my-room";
        roomName.textContent = r.room_name || "教室";

        const statusBadge = document.createElement("span");
        statusBadge.className = meta.cls;
        statusBadge.textContent = meta.text;

        top.appendChild(roomName);
        top.appendChild(statusBadge);

        const mid = document.createElement("div");
        mid.className = "my-mid";

        const dateItem = document.createElement("div");
        dateItem.className = "my-item";
        const dateKey = document.createElement("div");
        dateKey.className = "my-k";
        dateKey.textContent = "日付";
        const dateVal = document.createElement("div");
        dateVal.className = "my-v";
        dateVal.textContent = r.date;
        dateItem.appendChild(dateKey);
        dateItem.appendChild(dateVal);

        const periodItem = document.createElement("div");
        periodItem.className = "my-item";
        const periodKey = document.createElement("div");
        periodKey.className = "my-k";
        periodKey.textContent = "時限";
        const periodVal = document.createElement("div");
        periodVal.className = "my-v";
        periodVal.appendChild(document.createTextNode(`${r.period}限 `));
        const timeSpan = document.createElement("span");
        timeSpan.className = "my-time";
        timeSpan.textContent = `${times[0]}-${times[1]}`;
        periodVal.appendChild(timeSpan);
        periodItem.appendChild(periodKey);
        periodItem.appendChild(periodVal);

        mid.appendChild(dateItem);
        mid.appendChild(periodItem);

        const actions = document.createElement("div");
        actions.className = "my-actions";

        const detailLink = document.createElement("a");
        detailLink.className = "my-detail";
        detailLink.textContent = "詳細を見る";
        detailLink.href = r.detail_url;

        actions.appendChild(detailLink);

        card.appendChild(top);
        card.appendChild(mid);
        card.appendChild(actions);
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

    // URLに date= が付いて遷移してきた場合は、ページ表示後に自動でシートを開く
    if (location.search.includes("date=")) {
      setTimeout(() => {
        const iso = dateInput?.value ? dateInput.value : toISO(new Date());
        renderMyReservationsSheet(iso);
        openSheet();
      }, 250);
    }
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
