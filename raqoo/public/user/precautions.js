document.addEventListener("DOMContentLoaded", () => {
  // ==========================================
  // ここにはハンバーガーメニューのコードは書きません
  // home.js が自動でやってくれます
  // ==========================================

  // --- 以下、注意事項（3ステップ）のロジックのみ ---

  const mode = document.body.dataset.mode;
  const startStep = Number(document.body.dataset.startStep || 1);

  const cards = Array.from(document.querySelectorAll(".terms-card"));
  const dots = Array.from(document.querySelectorAll(".terms-dot"));
  const agreeBtn = document.getElementById("agreeBtn");

  // スマホ用：丸矢印ボタン
  const arrowPrev = document.getElementById("arrowPrev");
  const arrowNext = document.getElementById("arrowNext");

  // PC用：カード左半分・右半分クリックエリア
  const pcZonePrev = document.getElementById("pcZonePrev");
  const pcZoneNext = document.getElementById("pcZoneNext");

  const TOTAL = cards.length || 3;
  const STORAGE_KEY = "precautions_visited_steps_v1";

  // ─── セッション中に訪問済みのステップを記録する ───
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

  // ─── 同意ボタンの有効/無効を切り替える ───
  const updateAgree = () => {
    if (mode !== "confirm" || !agreeBtn) return;

    const ok = visited.size >= TOTAL;
    agreeBtn.disabled = !ok;
    agreeBtn.setAttribute("aria-disabled", ok ? "false" : "true");
  };

  // =========================================
  // 移動ボタン・クリックエリアの表示/非表示を切り替える
  // -----------------------------------------
  // ステップ1 → 左移動を隠す
  // ステップ3 → 右移動を隠す
  // ステップ2 → 両方表示
  // =========================================
  const updateMoveZones = () => {
    const isFirst = currentStep === 1;
    const isLast = currentStep === TOTAL;

    // スマホ用：丸矢印の表示制御
    if (arrowPrev) {
      arrowPrev.dataset.hidden = isFirst ? "true" : "false";
      arrowPrev.setAttribute("aria-hidden", isFirst ? "true" : "false");
      arrowPrev.tabIndex = isFirst ? -1 : 0;
    }

    if (arrowNext) {
      arrowNext.dataset.hidden = isLast ? "true" : "false";
      arrowNext.setAttribute("aria-hidden", isLast ? "true" : "false");
      arrowNext.tabIndex = isLast ? -1 : 0;
    }

    // PC用：左右クリックエリアの表示制御
    if (pcZonePrev) {
      pcZonePrev.dataset.hidden = isFirst ? "true" : "false";
      pcZonePrev.setAttribute("aria-hidden", isFirst ? "true" : "false");
      pcZonePrev.tabIndex = isFirst ? -1 : 0;
    }

    if (pcZoneNext) {
      pcZoneNext.dataset.hidden = isLast ? "true" : "false";
      pcZoneNext.setAttribute("aria-hidden", isLast ? "true" : "false");
      pcZoneNext.tabIndex = isLast ? -1 : 0;
    }
  };

  // ─── 指定ステップをアクティブにする ───
  const setActive = (step) => {
    if (step < 1 || step > TOTAL) return;

    currentStep = step;

    cards.forEach((c) => {
      const isActive = Number(c.dataset.step) === step;
      c.dataset.active = isActive ? "true" : "false";
      c.style.display = isActive ? "" : "none";
    });

    dots.forEach((d) => {
      const isActive = Number(d.dataset.stepjump) === step;
      d.dataset.active = isActive ? "true" : "false";
    });

    updateMoveZones();

    if (mode === "confirm") {
      visited.add(step);
      saveVisited(visited);
      updateAgree();
    }
  };

  // ─── 初期表示 ───
  if (mode === "confirm") {
    visited.add(currentStep);
    saveVisited(visited);
    updateAgree();
  }

  setActive(currentStep);

  // ─── 次へ・前へ ───
  const goNext = () => {
    if (currentStep >= TOTAL) return;
    setActive(currentStep + 1);
  };

  const goPrev = () => {
    if (currentStep <= 1) return;
    setActive(currentStep - 1);
  };

  // =========================================
  // スワイプで次・前に進む
  // スマホ向け
  // =========================================

  let touchStartX = 0;
  let touchStartY = 0;
  let isSwiping = false;

  const viewport = document.querySelector(".terms-viewport");

  if (viewport) {
    // --- 指が触れた瞬間 ---
    viewport.addEventListener("touchstart", (e) => {
      // ドット・スマホ矢印・PCクリックエリア部分のタッチは除外
      if (
        e.target.closest(".terms-dot") ||
        e.target.closest(".terms-arrow") ||
        e.target.closest(".terms-click-zone")
      ) {
        return;
      }

      const touch = e.touches[0];
      touchStartX = touch.clientX;
      touchStartY = touch.clientY;
      isSwiping = true;
    }, { passive: true });

    // --- 指を動かしている間 ---
    viewport.addEventListener("touchmove", (e) => {
      if (!isSwiping) return;

      const dx = Math.abs(e.touches[0].clientX - touchStartX);
      const dy = Math.abs(e.touches[0].clientY - touchStartY);

      if (dy > dx) {
        isSwiping = false;
      } else if (dx > 10) {
        e.preventDefault();
      }
    }, { passive: false });

    // --- 指を離した瞬間 ---
    viewport.addEventListener("touchend", (e) => {
      if (!isSwiping) return;

      isSwiping = false;

      const touchEndX = e.changedTouches[0].clientX;
      const diff = touchStartX - touchEndX;
      const threshold = 50;

      if (Math.abs(diff) < threshold) return;

      if (diff > 0) {
        goNext();
      } else {
        goPrev();
      }
    }, { passive: true });
  }

  // =========================================
  // PC判定
  // =========================================
  const isPC = () => window.matchMedia("(min-width: 769px)").matches;

  // =========================================
  // カードのキーボード操作
  // -----------------------------------------
  // カード全体クリックでは移動しない。
  // Enter / Space では次へ進める。
  // =========================================
  cards.forEach((c) => {
    c.addEventListener("keydown", (e) => {
      if (!isPC()) return;

      if ((e.key === "Enter" || e.key === " ") && Number(c.dataset.step) === currentStep) {
        e.preventDefault();
        goNext();
      }
    });
  });

  // =========================================
  // キーボード操作（←→キー）でも移動できるようにする
  // =========================================
  document.addEventListener("keydown", (e) => {
    if (e.key === "ArrowRight") {
      goNext();
    }

    if (e.key === "ArrowLeft") {
      goPrev();
    }
  });

  // ─── ドットのクリック ───
  dots.forEach((d) => {
    d.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();

      const step = Number(d.dataset.stepjump);

      if (Number.isFinite(step)) {
        setActive(step);
      }
    });
  });

  // =========================================
  // スマホ用：丸矢印ボタンのクリック
  // 左矢印 → 前へ
  // 右矢印 → 次へ
  // =========================================
  if (arrowPrev) {
    arrowPrev.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      goPrev();
    });
  }

  if (arrowNext) {
    arrowNext.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      goNext();
    });
  }

  // =========================================
  // PC用：カード左半分・右半分クリック
  // 左側 → 前へ
  // 右側 → 次へ
  // =========================================
  if (pcZonePrev) {
    pcZonePrev.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();

      if (!isPC()) return;

      goPrev();
    });
  }

  if (pcZoneNext) {
    pcZoneNext.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();

      if (!isPC()) return;

      goNext();
    });
  }

  // ─── 同意フォーム送信時にセッション情報をクリア ───
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