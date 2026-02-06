document.addEventListener("DOMContentLoaded", () => {
  const jaWeek = ["日", "月", "火", "水", "木", "金", "土"];
  const pad2 = (n) => String(n).padStart(2, "0");
  const toISO = (d) => `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
  const toMMDD = (d) => `${pad2(d.getMonth() + 1)}/${pad2(d.getDate())}`;

  const isMobile560 = () => window.matchMedia("(max-width: 560px)").matches;

  // DOM
  const mainEl = document.querySelector(".date-main");
  const wdayEl = document.querySelector(".date-weekday");
  const dateInput = document.querySelector("#dateInput");

  const tabs = document.querySelectorAll(".day-tabs .day-tab");
  const prevBtn = document.querySelector(".strip-prev");
  const nextBtn = document.querySelector(".strip-next");

  // --- シートDOM ---
  const openSheetBtn = document.querySelector("#openStatusSheet");
  const closeSheetBtn = document.querySelector("#closeStatusSheet");
  const sheet = document.querySelector("#statusSheet");
  const sheetBody = document.querySelector("#statusSheetBody");

  const openSheet = () => {
    document.body.classList.add("sheet-open");
    sheet?.setAttribute("aria-hidden", "false");
  };
  const closeSheet = () => {
    document.body.classList.remove("sheet-open");
    sheet?.setAttribute("aria-hidden", "true");
  };

  // ---------- ステータス表示（日本語＆色用class） ----------
  function statusMeta(status) {
    switch (status) {
      case "approved":   return { text: "承認済み", cls: "my-badge approved" };
      case "pending":    return { text: "申請中",   cls: "my-badge pending" };
      case "rejected":   return { text: "却下",     cls: "my-badge rejected" };
      case "cancelled":  return { text: "キャンセル", cls: "my-badge cancelled" };
      case "completed":  return { text: "完了",     cls: "my-badge completed" };
      case "reserved":   return { text: "予約済み", cls: "my-badge reserved" }; // もし返るなら変換
      case "in_use":     return { text: "利用中",   cls: "my-badge in_use" };
      default:           return { text: status || "不明", cls: "my-badge other" };
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
  async function renderMyReservationsSheet(dateISO) {
    if (!sheetBody) return;

    sheetBody.textContent = "読み込み中...";

    try {
      const res = await fetch(`/user/my-reservations?date=${encodeURIComponent(dateISO)}`, { cache: "no-store" });
      const rows = await res.json();

      // 承認済みが一番上に来る優先ソート
      const priority = (st) => {
        if (st === "approved") return 0;
        if (st === "pending") return 1;
        if (st === "reserved") return 2;
        return 9;
      };

      rows.sort((a, b) => {
        const pa = priority(a.status);
        const pb = priority(b.status);
        if (pa !== pb) return pa - pb;
        if (a.date === b.date) return (a.period || 0) - (b.period || 0);
        return String(a.date).localeCompare(String(b.date));
      });

      if (!rows.length) {
        sheetBody.innerHTML = `<div class="my-empty">この日の予約はありません</div>`;
        return;
      }

      const html = rows.map((r) => {
        const meta = statusMeta(r.status);
        const times = timeTable[r.period] || ["--:--", "--:--"];
        const isApproved = r.status === "approved";

        return `
          <div class="my-card ${isApproved ? "is-approved" : ""}">
            <div class="my-top">
              <div class="my-room">${r.room_name || "教室"}</div>
              <span class="${meta.cls}">${meta.text}</span>
            </div>

            <div class="my-mid">
              <div class="my-item">
                <div class="my-k">日付</div>
                <div class="my-v">${r.date}</div>
              </div>
              <div class="my-item">
                <div class="my-k">時限</div>
                <div class="my-v">${r.period}限 <span class="my-time">${times[0]}-${times[1]}</span></div>
              </div>
            </div>

            <div class="my-actions">
              <a class="my-detail" href="${r.detail_url}">詳細を見る</a>
            </div>
          </div>
        `;
      }).join("");

      sheetBody.innerHTML = html;
    } catch (e) {
      console.error(e);
      sheetBody.innerHTML = `<div class="my-empty">取得に失敗しました</div>`;
    }
  }

  // ---------- シート開閉 ----------
  if (isMobile560()) {
    openSheetBtn?.addEventListener("click", () => {
      // 開いた瞬間にその日の予約を読み込み
      const iso = dateInput?.value ? dateInput.value : toISO(new Date());
      renderMyReservationsSheet(iso);
      openSheet();
    });

    closeSheetBtn?.addEventListener("click", closeSheet);

    // 日付検索後に自動で開く（date= 付きなら）
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
    const [year, month, day] = dateInput.value.split("-").map(Number);
    selectedDate = new Date(year, month - 1, day);
  } else {
    selectedDate = new Date();
  }
  let startDate = new Date(selectedDate.getTime() - selectedDate.getDay() * 86400000);

  // 中央の日付更新
  function updateCenterDisplay(d) {
    if (mainEl) mainEl.textContent = toMMDD(d);
    if (wdayEl) wdayEl.textContent = `（${jaWeek[d.getDay()]}）`;
    if (dateInput) dateInput.value = toISO(d);

    // 教室カードの状態更新（ここは既存通り）
    updateRoomStatuses(toISO(d));

    // シートが開いてるなら、中身もその日付に更新
    if (document.body.classList.contains("sheet-open") && isMobile560()) {
      renderMyReservationsSheet(toISO(d));
    }
  }

  // 教室ステータスをサーバーから取得して更新（既存のまま）
  function updateRoomStatuses(date) {
    document.querySelectorAll(".room-group").forEach((roomGroup) => {
      roomGroup.querySelectorAll(".time-card").forEach((card) => {
        card.classList.remove("available");
        card.classList.add("unavailable");

        const availableLabel = card.querySelector(".available-label");
        const unavailableLabel = card.querySelector(".unavailable-label");
        const reserveBtn = card.querySelector(".reserve-btn");

        if (availableLabel) availableLabel.style.display = "none";
        if (unavailableLabel) {
          unavailableLabel.style.display = "";
          unavailableLabel.textContent = "確認中";
        }

        if (reserveBtn) {
          reserveBtn.style.display = "none";
          let href = reserveBtn.getAttribute("href");
          if (href) {
            href = href.replace(/date%3D\d{4}-\d{2}-\d{2}/g, `date%3D${date}`);
            href = href.replace(/date=\d{4}-\d{2}-\d{2}/g, `date=${date}`);
            reserveBtn.setAttribute("href", href);
          }
        }
      });
    });

    fetch(`/user/room-status?date=${date}`, { cache: "no-store" })
      .then((response) => response.json())
      .then((data) => {
        document.querySelectorAll(".room-group").forEach((roomGroup) => {
          const roomId = roomGroup.dataset.roomId;

          roomGroup.querySelectorAll(".time-card").forEach((card) => {
            const period = Number(card.dataset.period);

            const statusData = data.find(
              (item) => item.room_id == roomId && Number(item.period) === period
            );

            const status = statusData ? statusData.status : "available";

            card.classList.remove("available", "unavailable");
            card.classList.add(status === "available" ? "available" : "unavailable");

            const availableLabel = card.querySelector(".available-label");
            const unavailableLabel = card.querySelector(".unavailable-label");
            const reserveBtn = card.querySelector(".reserve-btn");

            if (status === "available") {
              if (availableLabel) {
                availableLabel.style.display = "";
                availableLabel.textContent = "利用可能";
              }
              if (unavailableLabel) unavailableLabel.style.display = "none";
              if (reserveBtn) reserveBtn.style.display = "";
            } else {
              if (availableLabel) availableLabel.style.display = "none";
              if (unavailableLabel) {
                unavailableLabel.style.display = "";
                unavailableLabel.textContent =
                  status === "pending" ? "申請中" :
                  status === "in_use" ? "利用中" :
                  status === "reserved" ? "予約済み" : "利用不可";
              }
              if (reserveBtn) reserveBtn.style.display = "none";
            }
          });
        });
      })
      .catch((error) => console.error("ステータス取得エラー:", error));
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
  tabs.forEach((tab) => {
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

  prevBtn?.addEventListener("click", () => {
    selectedDate = new Date(selectedDate.getTime() - 86400000);
    startDate = new Date(startDate.getTime() - 86400000);
    updateCenterDisplay(selectedDate);
    renderStrip();
  });

  nextBtn?.addEventListener("click", () => {
    selectedDate = new Date(selectedDate.getTime() + 86400000);
    startDate = new Date(startDate.getTime() + 86400000);
    updateCenterDisplay(selectedDate);
    renderStrip();
  });

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
