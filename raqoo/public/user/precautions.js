document.addEventListener("DOMContentLoaded", () => {
  const mode = document.body.dataset.mode; // 'confirm' or 'terms'
  const startStep = Number(document.body.dataset.startStep || 1);

  const cards = Array.from(document.querySelectorAll(".terms-card"));
  const dots = Array.from(document.querySelectorAll(".terms-dot"));
  const agreeBtn = document.getElementById("agreeBtn");

  const TOTAL = cards.length || 3;

  // 3ステップ閲覧状況（confirm時のみ使う）
  const visited = new Set();

  // 初期：startStep を visited に入れる（confirm時のみ）
  if (mode === "confirm") {
    visited.add(startStep);
  }

  // 表示切替
  const setActive = (step) => {
    // カード切替
    cards.forEach((c) => {
      const isActive = Number(c.dataset.step) === step;
      c.dataset.active = isActive ? "true" : "false";
      c.style.display = isActive ? "block" : "none";
    });

    // ドット更新
    dots.forEach((d) => {
      const isActive = Number(d.dataset.stepjump) === step;
      d.dataset.active = isActive ? "true" : "false";
    });

    // confirm（予約フロー）のときだけ「全部見たら同意ボタン解除」
    if (mode === "confirm") {
      visited.add(step);

      if (agreeBtn) {
        const ok = visited.size >= TOTAL;
        agreeBtn.disabled = !ok;
        agreeBtn.setAttribute("aria-disabled", ok ? "false" : "true");
      }
    }
  };

  // 初期表示
  setActive(startStep);

  // カードクリックで次へ（ドットは除外）
  cards.forEach((c) => {
    c.addEventListener("click", (e) => {
      if (e.target.closest(".terms-dot")) return;

      const current = Number(
        (document.querySelector('.terms-card[data-active="true"]') || {}).dataset
          ?.step || startStep
      );
      const next = (current % TOTAL) + 1;
      setActive(next);
    });
  });

  // ドットでジャンプ
  dots.forEach((d) => {
    d.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      setActive(Number(d.dataset.stepjump));
    });
  });
});
