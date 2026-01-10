document.addEventListener("DOMContentLoaded", () => {
  // ==========================================
  // ここにはハンバーガーメニューのコードは書きません
  // (home.js が自動でやってくれます)
  // ==========================================

  // --- 以下、注意事項（3ステップ）のロジックのみ ---

  const mode = document.body.dataset.mode; 
  const startStep = Number(document.body.dataset.startStep || 1);

  const cards = Array.from(document.querySelectorAll(".terms-card"));
  const dots = Array.from(document.querySelectorAll(".terms-dot"));
  const agreeBtn = document.getElementById("agreeBtn");

  const TOTAL = cards.length || 3;
  const STORAGE_KEY = "precautions_visited_steps_v1";

  const loadVisited = () => {
    try {
      const raw = sessionStorage.getItem(STORAGE_KEY);
      if (!raw) return new Set();
      const arr = JSON.parse(raw);
      if (!Array.isArray(arr)) return new Set();
      return new Set(arr.map((n) => Number(n)).filter((n) => Number.isFinite(n)));
    } catch { return new Set(); }
  };

  const saveVisited = (set) => {
    try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(set))); } catch {}
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
      if (isActive) c.focus({ preventScroll: true });
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

  // 初期化
  if (mode === "confirm") {
    visited.add(currentStep);
    saveVisited(visited);
    updateAgree();
  }
  setActive(currentStep);

  const goNext = () => {
    const next = (currentStep % TOTAL) + 1;
    setActive(next);
  };

  cards.forEach((c) => {
    c.addEventListener("click", (e) => {
      if (e.target.closest(".terms-dot")) return;
      if (Number(c.dataset.step) === currentStep) goNext();
    });
    c.addEventListener("keydown", (e) => {
      if ((e.key === "Enter" || e.key === " ") && Number(c.dataset.step) === currentStep) {
        e.preventDefault();
        goNext();
      }
    });
  });

  dots.forEach((d) => {
    d.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      const step = Number(d.dataset.stepjump);
      if (Number.isFinite(step)) setActive(step);
    });
  });

  if (mode === "confirm" && agreeBtn) {
    const form = agreeBtn.closest("form");
    if (form) {
      form.addEventListener("submit", () => {
        try { sessionStorage.removeItem(STORAGE_KEY); } catch {}
      });
    }
  }
});