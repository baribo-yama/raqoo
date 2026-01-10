document.addEventListener("DOMContentLoaded", () => {
  const mode = document.body.dataset.mode; // 'confirm' or 'terms'
  const startStep = Number(document.body.dataset.startStep || 1);

  const cards = Array.from(document.querySelectorAll(".terms-card"));
  const dots = Array.from(document.querySelectorAll(".terms-dot"));
  const agreeBtn = document.getElementById("agreeBtn");

  const TOTAL = cards.length || 3;

  // confirm 時だけ visited を保持（リロード対策）
  const STORAGE_KEY = "precautions_visited_steps_v1";

  const loadVisited = () => {
    try {
      const raw = sessionStorage.getItem(STORAGE_KEY);
      if (!raw) return new Set();
      const arr = JSON.parse(raw);
      if (!Array.isArray(arr)) return new Set();
      return new Set(arr.map((n) => Number(n)).filter((n) => Number.isFinite(n)));
    } catch {
      return new Set();
    }
  };

  const saveVisited = (set) => {
    try {
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(set)));
    } catch {}
  };

  const visited = mode === "confirm" ? loadVisited() : new Set();

  let currentStep = Math.min(Math.max(startStep, 1), TOTAL);

  const updateAgree = () => {
    if (mode !== "confirm" || !agreeBtn) return;
    const ok = visited.size >= TOTAL;
    agreeBtn.disabled = !ok;
    agreeBtn.setAttribute("aria-disabled", ok ? "false" : "true");
  };

  const setActive = (step) => {
    currentStep = step;

    cards.forEach((c) => {
      const isActive = Number(c.dataset.step) === step;
      c.dataset.active = isActive ? "true" : "false";
      c.style.display = isActive ? "block" : "none";

      // アクティブカードにフォーカス（キーボード利用者向け）
      if (isActive) {
        c.focus({ preventScroll: true });
      }
    });

    dots.forEach((d) => {
      const isActive = Number(d.dataset.stepjump) === step;
      d.dataset.active = isActive ? "true" : "false";
    });

    if (mode === "confirm") {
      visited.add(step);
      saveVisited(visited);
      updateAgree();
    }
  };

  // 初期状態：startStep も visited に入れる（confirmのみ）
  if (mode === "confirm") {
    visited.add(currentStep);
    saveVisited(visited);
    updateAgree();
  }

  // 初期表示
  setActive(currentStep);

  const goNext = () => {
    const next = (currentStep % TOTAL) + 1;
    setActive(next);
  };

  // カードクリックで次へ（ドットは除外）
  cards.forEach((c) => {
    c.addEventListener("click", (e) => {
      if (e.target.closest(".terms-dot")) return;

      // ★修正: クリックされたカードが「現在アクティブなステップ」か確認
      const step = Number(c.dataset.step);
      if (step === currentStep) {
        goNext();
      }
    });

    // ✅ キーボード操作：Enter / Space で次へ
    c.addEventListener("keydown", (e) => {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault(); // Spaceのスクロール等を防止
        
        // ★修正: 重複発火防止のため、ここでも現在ステップかチェック
        const step = Number(c.dataset.step);
        if (step === currentStep) {
          goNext();
        }
      }
    });
  });

  // ドットでジャンプ
  dots.forEach((d) => {
    d.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      const step = Number(d.dataset.stepjump);
      if (!Number.isFinite(step)) return;
      setActive(step);
    });
  });

  // confirm では「同意して予約へ進む」押したら visited をクリア（次の予約フローに影響させない）
  if (mode === "confirm" && agreeBtn) {
    const form = agreeBtn.closest("form");
    if (form) {
      form.addEventListener("submit", () => {
        try {
          sessionStorage.removeItem(STORAGE_KEY);
        } catch {}
      });
    }
  }
});