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

  // 【追加④】左右の矢印ボタン
  const arrowPrev = document.getElementById("arrowPrev");
  const arrowNext = document.getElementById("arrowNext");

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
  // 【修正】矢印の表示/非表示を切り替える
  // -----------------------------------------
  // data-hidden だけだと見えない矢印にTabフォーカスできるため、
  // hidden / disabled / aria-hidden / tabindex も同時に切り替える。
  // =========================================
  const setArrowVisibility = (arrow, isHidden) => {
    if (!arrow) return;

    arrow.dataset.hidden = isHidden ? "true" : "false";
    arrow.hidden = isHidden;
    arrow.disabled = isHidden;
    arrow.setAttribute("aria-hidden", isHidden ? "true" : "false");

    if (isHidden) {
      arrow.setAttribute("tabindex", "-1");
    } else {
      arrow.removeAttribute("tabindex");
    }
  };

  const updateArrows = () => {
    setArrowVisibility(arrowPrev, currentStep === 1);
    setArrowVisibility(arrowNext, currentStep === TOTAL);
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

    // 矢印の表示も更新
    updateArrows();

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
  // modulo による循環をやめ、端では移動しないようにする
  const goNext = () => {
    if (currentStep >= TOTAL) return;
    setActive(currentStep + 1);
  };

  const goPrev = () => {
    if (currentStep <= 1) return;
    setActive(currentStep - 1);
  };

  // =========================================
  // 【追加①】スワイプで次・前に進む
  // -----------------------------------------
  // 仕組み：
  //   指が画面に触れた瞬間の位置を記録（touchstart）
  //   → 指を動かしている間、横移動か縦移動かを判定（touchmove）
  //   → 指を離したとき、横に50px以上動いていたらスワイプと判定（touchend）
  //     左スワイプ → 次のステップへ
  //     右スワイプ → 前のステップへ
  //     縦方向の動きが大きい場合は通常のスクロール扱い
  // =========================================

  let touchStartX = 0;
  let touchStartY = 0;
  let isSwiping = false;

  const viewport = document.querySelector(".terms-viewport");

  if (viewport) {
    // --- 指が触れた瞬間 ---
    viewport.addEventListener("touchstart", (e) => {
      // ドットや矢印部分のタッチは除外（それぞれ別の処理がある）
      if (e.target.closest(".terms-dot") || e.target.closest(".terms-arrow")) return;

      const touch = e.touches[0];
      touchStartX = touch.clientX;
      touchStartY = touch.clientY;
      isSwiping = true;
    }, { passive: true });

    // --- 指を動かしている間 ---
    // passive: false にすることで
    // 「ブラウザの横スワイプ（ページ戻る等）を止める権限」を得る。
    viewport.addEventListener("touchmove", (e) => {
      if (!isSwiping) return;

      const dx = Math.abs(e.touches[0].clientX - touchStartX);
      const dy = Math.abs(e.touches[0].clientY - touchStartY);

      if (dy > dx) {
        // 縦方向の動きが大きい → 普通のスクロール扱い、何もしない
        isSwiping = false;
      } else if (dx > 10) {
        // 横方向の動きが大きい → ブラウザの「戻る」や横スクロールを止める
        e.preventDefault();
      }
    }, { passive: false });

    // --- 指を離した瞬間 ---
    viewport.addEventListener("touchend", (e) => {
      if (!isSwiping) return;

      isSwiping = false;

      const touchEndX = e.changedTouches[0].clientX;
      const diff = touchStartX - touchEndX;
      const threshold = 50; // 50px以上動かしたらスワイプと判定

      if (Math.abs(diff) < threshold) return;

      if (diff > 0) {
        goNext();  // ← 左にスワイプ → 次へ
      } else {
        goPrev();  // → 右にスワイプ → 前へ
      }
    }, { passive: true });
  }

  // =========================================
  // 【変更②】カード全面タップ → 削除
  // -----------------------------------------
  // 以前はカードをタップすると次に進んでいたが、
  // スワイプと併用すると誤操作になるため削除。
  // 代わりにドットのタップと左右キーで移動できる。
  // =========================================

  // （カードのクリック/キーダウンのイベントは登録しない）

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

  // ─── ドットのクリックは従来通り ───
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
  // 【追加④】矢印ボタンのクリック
  // -----------------------------------------
  // 左矢印 → 前のステップへ
  // 右矢印 → 次のステップへ
  // ステップ1で左、ステップ3で右は
  // updateArrows() で非表示にしているので押せない。
  // =========================================
  if (arrowPrev) {
    arrowPrev.addEventListener("click", (e) => {
      e.preventDefault();
      goPrev();
    });
  }

  if (arrowNext) {
    arrowNext.addEventListener("click", (e) => {
      e.preventDefault();
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