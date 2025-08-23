(() => {
  const cards = document.querySelectorAll('.card');
  const dots  = document.querySelectorAll('.dot');
  const TOTAL = 3;
  let current = 1;

  function setActive(step){
    current = step;
    // カード切替
    cards.forEach(c => c.style.display = (Number(c.dataset.step) === step) ? 'block' : 'none');
    // ドット更新
    dots.forEach(d => d.dataset.active = (Number(d.dataset.stepjump) === step) ? 'true' : 'false');
  }

  function next(){ setActive(current % TOTAL + 1); }

  // カード全体クリックで次へ（ドット/バッジは除外）
  cards.forEach(c => {
    c.addEventListener('click', (e)=>{
      if(e.target.closest('.dot, .badge')) return;
      next();
    });
  });

  // ドットで直接ジャンプ
  dots.forEach(d => d.addEventListener('click', (e)=>{
    e.stopPropagation();
    setActive(Number(d.dataset.stepjump));
  }));

  // ←/→ でも切替
  window.addEventListener('keydown', (e)=>{
    if(e.key === 'ArrowRight') next();
    if(e.key === 'ArrowLeft')  setActive((current - 2 + TOTAL) % TOTAL + 1);
  });

  setActive(1);
})();
