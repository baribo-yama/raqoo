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
  let startDate = new Date(selectedDate.getTime() - selectedDate.getDay() * 86400000);

  // 中央の日付更新
  function updateCenterDisplay(d) {
    mainEl.textContent = toMMDD(d);
    wdayEl.textContent = `（${jaWeek[d.getDay()]}）`;
    if (dateInput) dateInput.value = toISO(d);
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
    startDate   = new Date(startDate.getTime()   - 86400000);

    updateCenterDisplay(selectedDate);
    renderStrip();
  });

  // 次へ
  nextBtn?.addEventListener("click", () => {
    selectedDate = new Date(selectedDate.getTime() + 86400000);
    startDate   = new Date(startDate.getTime()   + 86400000);

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
