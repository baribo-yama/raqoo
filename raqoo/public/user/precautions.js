document.addEventListener('DOMContentLoaded', () => {
  const mode = document.body.dataset.mode || 'terms'; // confirm or terms
  const agreeBtn = document.getElementById('agreeBtn');

  const cards = [...document.querySelectorAll('.terms-card[data-step]')];
  const dots  = [...document.querySelectorAll('.terms-dot[data-stepjump]')];

  const TOTAL = 3;
  let current = 1;
  const visited = new Set();

  const setStep = (n) => {
    current = n;

    cards.forEach(card => {
      card.dataset.active = (Number(card.dataset.step) === n) ? 'true' : 'false';
    });

    dots.forEach(dot => {
      dot.dataset.active = (Number(dot.dataset.stepjump) === n) ? 'true' : 'false';
    });

    visited.add(n);

    // confirm（/precautions）の時だけ有効化
    if (mode === 'confirm' && agreeBtn) {
      const ok = visited.size >= TOTAL;
      agreeBtn.disabled = !ok;
      agreeBtn.setAttribute('aria-disabled', ok ? 'false' : 'true');
    }
  };

  const next = () => setStep((current % TOTAL) + 1);

  // カードクリックで次へ（dotは除外）
  cards.forEach(c => c.addEventListener('click', (e) => {
    if (e.target.closest('.terms-dot')) return;
    next();
  }));

  // dotでジャンプ
  dots.forEach(d => d.addEventListener('click', (e) => {
    e.stopPropagation();
    setStep(Number(d.dataset.stepjump));
  }));

  // 初期表示
  setStep(1);
});
