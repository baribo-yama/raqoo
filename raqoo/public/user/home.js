document.addEventListener("DOMContentLoaded", () => {

  const jaWeek = ["日","月","火","水","木","金","土"];
  const pad2 = (n) => String(n).padStart(2, "0");
  const toISO = (d) => `${d.getFullYear()}-${pad2(d.getMonth()+1)}-${pad2(d.getDate())}`;
  const toMMDD = (d) => `${pad2(d.getMonth()+1)}/${pad2(d.getDate())}`;

  // DOM
  const mainEl  = document.querySelector(".date-main");
  const wdayEl  = document.querySelector(".date-weekday");
  const dateInput = document.querySelector("#dateInput");

  const tabs = document.querySelectorAll(".day-tabs .day-tab");
  const prevBtn = document.querySelector(".strip-prev");
  const nextBtn = document.querySelector(".strip-next");

  // 日付
  let selectedDate = new Date();
  let startDate = new Date();
  startDate.setDate(selectedDate.getDate() - selectedDate.getDay()); // 日曜開始

  // 中央の日付更新
  function updateCenterDisplay(d) {
    mainEl.textContent = toMMDD(d);
    wdayEl.textContent = `（${jaWeek[d.getDay()]}）`;
    if (dateInput) dateInput.value = toISO(d);
  }

  // 7つのタブ表示
  function renderStrip() {
    tabs.forEach((tab, i) => {
      const d = new Date(startDate);
      d.setDate(startDate.getDate() + i);

      tab.querySelector(".day-label").textContent = jaWeek[d.getDay()];
      tab.querySelector(".date-label").textContent = toMMDD(d);

      tab.dataset.date = toISO(d);

      // active
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
      selectedDate = new Date(iso);

      updateCenterDisplay(selectedDate);

      // 週の開始を調整
      startDate = new Date(selectedDate);
      startDate.setDate(selectedDate.getDate() - selectedDate.getDay());

      renderStrip();
    });
  });

  // 前へ
  prevBtn?.addEventListener("click", () => {
    selectedDate.setDate(selectedDate.getDate() - 1);
    updateCenterDisplay(selectedDate);

    startDate.setDate(startDate.getDate() - 1);
    renderStrip();
  });

  // 次へ
  nextBtn?.addEventListener("click", () => {
    selectedDate.setDate(selectedDate.getDate() + 1);
    updateCenterDisplay(selectedDate);

    startDate.setDate(startDate.getDate() + 1);
    renderStrip();
  });

  // 入力フォーム（日付変更）
  dateInput?.addEventListener("change", () => {
    const d = new Date(dateInput.value);
    if (isNaN(d)) return;

    selectedDate = d;
    updateCenterDisplay(selectedDate);

    startDate = new Date(selectedDate);
    startDate.setDate(selectedDate.getDate() - selectedDate.getDay());

    renderStrip();
  });

});
