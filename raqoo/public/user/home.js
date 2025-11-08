document.addEventListener('DOMContentLoaded', () => {
  // ===== Utils =====
  const jaWeek = ['日','月','火','水','木','金','土'];
  const pad2 = n => String(n).padStart(2, '0');
  const toISO = d => `${d.getFullYear()}-${pad2(d.getMonth()+1)}-${pad2(d.getDate())}`;
  const toMMDD = d => `${pad2(d.getMonth()+1)}/${pad2(d.getDate())}`;

  // ===== DOM =====
  const yearEl = document.querySelector('.date-year');
  const mainEl = document.querySelector('.date-main');
  const wdayEl = document.querySelector('.date-weekday');

  const form = document.querySelector('.search-form');
  const dateInput = form?.querySelector('input[type="date"]');

  const tabs = Array.from(document.querySelectorAll('.day-tabs .day-tab'));
  const prevBtn = document.querySelector('.day-tabs .strip-prev');
  const nextBtn = document.querySelector('.day-tabs .strip-next');

  // ===== Central date helpers =====
  function setCenterDate(d) {
    if (!d || Number.isNaN(d.getTime())) return;
    yearEl.textContent = d.getFullYear();
    mainEl.textContent = toMMDD(d);
    wdayEl.textContent = `（${jaWeek[d.getDay()]}）`;
    if (dateInput) dateInput.value = toISO(d);
  }

  function getCenterDate() {
    const y  = parseInt(yearEl?.textContent || '2025', 10);
    const [m, d] = (mainEl?.textContent || '01/01').split('/').map(v => parseInt(v, 10));
    const dt = new Date(y, (m - 1), d);
    dt.setHours(0,0,0,0);
    return dt;
  }

  // ===== State =====
  // startDate: ストリップ左端の日付
  // selectedDate: 中央表示の日付（タブクリック/検索で更新）
  let selectedDate = getCenterDate();

  function startOfWeek(d) {
    const s = new Date(d);
    s.setHours(0,0,0,0);
    s.setDate(d.getDate() - d.getDay()); // 日曜始まり
    return s;
  }
  let startDate = startOfWeek(selectedDate);

  // ===== Render strip (7コマ) =====
  function renderStrip() {
    for (let i = 0; i < tabs.length; i++) {
      const tab = tabs[i];
      const d = new Date(startDate);
      d.setDate(startDate.getDate() + i);

      // 表示
      const dayLabel  = tab.querySelector('.day-label');
      const dateLabel = tab.querySelector('.date-label');
      if (dayLabel)  dayLabel.textContent  = jaWeek[d.getDay()];
      if (dateLabel) dateLabel.textContent = toMMDD(d);

      // クリック用
      tab.dataset.date = toISO(d);

      // active（中央と一致したタブ）
      const isSelected =
        d.getFullYear() === selectedDate.getFullYear() &&
        d.getMonth()    === selectedDate.getMonth() &&
        d.getDate()     === selectedDate.getDate();
      tab.classList.toggle('active', isSelected);
    }
  }

  // 初期表示
  setCenterDate(selectedDate);
  renderStrip();

  // ===== Tab click -> central update =====
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      const iso = tab.dataset.date;
      if (!iso) return;
      selectedDate = new Date(iso);
      setCenterDate(selectedDate);
      renderStrip();
    });
  });

  // ===== Search submit -> reset week & central =====
  if (form && dateInput) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      if (!dateInput.value) return;
      const d = new Date(dateInput.value);
      if (Number.isNaN(d.getTime())) return;
      selectedDate = d;
      startDate = startOfWeek(d);  // ←入力日の週を左端(日曜)に
      setCenterDate(selectedDate);
      renderStrip();
    });
  }

  // ===== Arrow buttons -> slide by 1 day =====
  prevBtn?.addEventListener('click', () => {
    startDate.setDate(startDate.getDate() - 1);
    renderStrip();
  });
  nextBtn?.addEventListener('click', () => {
    startDate.setDate(startDate.getDate() + 1);
    renderStrip();
  });

  // ===== (既存) time-card selection =====
  document.querySelectorAll('.time-card').forEach(card => {
    card.addEventListener('click', function () {
      if (card.classList.contains('unavailable')) return;
      document.querySelectorAll('.time-card.active').forEach(a => a.classList.remove('active'));
      card.classList.add('active');
    });
  });
});
