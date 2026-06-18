document.addEventListener("DOMContentLoaded", () => {
    /*
        precautions.js

        役割：
        - 注意事項カード STEP1〜STEP3 の切り替え
        - ドット・スマホ用左右矢印・PC用左右クリックエリアでの移動
        - PCでの左右キー操作
        - スマホの横スワイプ操作
        - 同意モード時の「全ステップ確認済み」判定

        注意：
        - サイドバーのハンバーガーメニュー処理はここでは行わない
        - サイドバー開閉は home.js などの共通JS側で扱う
    */

    const mode = document.body.dataset.mode;
    const startStep = Number(document.body.dataset.startStep || 1);

    const viewport = document.querySelector(".terms-viewport");
    const cards = Array.from(document.querySelectorAll(".terms-card"));
    const dots = Array.from(document.querySelectorAll(".terms-dot"));
    const agreeBtn = document.getElementById("agreeBtn");

    const arrowPrev = document.getElementById("arrowPrev");
    const arrowNext = document.getElementById("arrowNext");

    const pcZonePrev = document.getElementById("pcZonePrev");
    const pcZoneNext = document.getElementById("pcZoneNext");

    const TOTAL_STEPS = cards.length;
    const STORAGE_KEY = "precautions_visited_steps_v1";

    if (!viewport || TOTAL_STEPS === 0) {
        return;
    }

    let currentStep = clampStep(startStep);
    const visitedSteps = mode === "confirm" ? loadVisitedSteps() : new Set();

    setActiveStep(currentStep, { focus: false });

    setupDotNavigation();
    setupArrowNavigation();
    setupPcClickZones();
    setupSwipeNavigation();
    setupKeyboardNavigation();
    setupAgreeFormCleanup();

    /*
        画面幅判定。
        CSS側の media query と合わせるため、769px以上をPC扱いにする。
    */
    function isPcLayout() {
        return window.matchMedia("(min-width: 769px)").matches;
    }

    /*
        指定されたステップ番号を、存在する範囲内に収める。
    */
    function clampStep(step) {
        if (!Number.isFinite(step)) {
            return 1;
        }

        return Math.min(Math.max(step, 1), TOTAL_STEPS);
    }

    /*
        sessionStorage から確認済みステップを復元する。
        不正な値や範囲外の値は除外する。
    */
    function loadVisitedSteps() {
        try {
            const rawSteps = sessionStorage.getItem(STORAGE_KEY);

            if (!rawSteps) {
                return new Set();
            }

            const parsedSteps = JSON.parse(rawSteps);

            if (!Array.isArray(parsedSteps)) {
                return new Set();
            }

            return new Set(
                parsedSteps
                    .map((step) => Number(step))
                    .filter((step) => Number.isInteger(step))
                    .filter((step) => step >= 1 && step <= TOTAL_STEPS),
            );
        } catch {
            return new Set();
        }
    }

    /*
        確認済みステップを sessionStorage に保存する。
        保存できない環境では、画面操作を止めずに続行する。
    */
    function saveVisitedSteps() {
        try {
            sessionStorage.setItem(
                STORAGE_KEY,
                JSON.stringify(Array.from(visitedSteps)),
            );
        } catch {
            // sessionStorage が使えない環境では保存せずに続行する
        }
    }

    /*
        confirmモード時、現在のステップを確認済みとして記録する。
    */
    function markCurrentStepAsVisited() {
        if (mode !== "confirm") {
            return;
        }

        visitedSteps.add(currentStep);
        saveVisitedSteps();
        updateAgreeButtonState();
    }

    /*
        全ステップ確認済みなら、同意ボタンを押せる状態にする。
    */
    function updateAgreeButtonState() {
        if (mode !== "confirm" || !agreeBtn) {
            return;
        }

        const isAllVisited = visitedSteps.size >= TOTAL_STEPS;

        agreeBtn.disabled = !isAllVisited;
        agreeBtn.setAttribute("aria-disabled", isAllVisited ? "false" : "true");
    }

    /*
        左右移動UIの表示状態を更新する。
        hidden / aria-hidden / tabindex をまとめて制御し、非表示要素にTabフォーカスが当たらないようにする。
    */
    function setMoveElementVisibility(
        element,
        isHidden,
        shouldUseDisabled = false,
    ) {
        if (!element) {
            return;
        }

        element.dataset.hidden = isHidden ? "true" : "false";
        element.hidden = isHidden;
        element.setAttribute("aria-hidden", isHidden ? "true" : "false");

        if (shouldUseDisabled) {
            element.disabled = isHidden;
        }

        if (isHidden) {
            element.setAttribute("tabindex", "-1");
            return;
        }

        element.removeAttribute("tabindex");
    }

    /*
        STEP1では左移動を隠し、最終STEPでは右移動を隠す。
        スマホ用矢印とPC用クリックエリアの両方をここで更新する。
    */
    function updateMoveZones() {
        const isFirstStep = currentStep === 1;
        const isLastStep = currentStep === TOTAL_STEPS;

        setMoveElementVisibility(arrowPrev, isFirstStep, true);
        setMoveElementVisibility(arrowNext, isLastStep, true);

        setMoveElementVisibility(pcZonePrev, isFirstStep, false);
        setMoveElementVisibility(pcZoneNext, isLastStep, false);
    }

    /*
        表示中カード内の操作要素へフォーカスを移す。
        キーボード操作時に、現在位置を見失いにくくするため。
    */
    function focusActiveStep(activeCard) {
        if (!activeCard) {
            return;
        }

        const focusTarget = activeCard.querySelector(
            "button:not([disabled]), a, input, select, textarea, [tabindex]:not([tabindex='-1'])",
        );

        focusTarget?.focus?.({ preventScroll: true });
    }

    /*
        指定ステップを表示し、カード・ドット・移動UI・同意ボタン状態をまとめて更新する。
    */
    function setActiveStep(step, options = {}) {
        if (step < 1 || step > TOTAL_STEPS) {
            return;
        }

        const shouldFocus = options.focus !== false;

        currentStep = step;

        let activeCard = null;

        for (const card of cards) {
            const isActive = Number(card.dataset.step) === currentStep;

            card.dataset.active = isActive ? "true" : "false";
            card.style.display = isActive ? "" : "none";

            if (isActive) {
                activeCard = card;
            }
        }

        for (const dot of dots) {
            const isActive = Number(dot.dataset.stepjump) === currentStep;
            dot.dataset.active = isActive ? "true" : "false";
        }

        updateMoveZones();
        markCurrentStepAsVisited();

        if (shouldFocus) {
            focusActiveStep(activeCard);
        }
    }

    /*
        次のステップへ移動する。
        最終STEPでは何もしない。
    */
    function goNext() {
        if (currentStep >= TOTAL_STEPS) {
            return;
        }

        setActiveStep(currentStep + 1);
    }

    /*
        前のステップへ移動する。
        STEP1では何もしない。
    */
    function goPrev() {
        if (currentStep <= 1) {
            return;
        }

        setActiveStep(currentStep - 1);
    }

    /*
        ドットクリックで指定ステップへ移動する。
    */
    function setupDotNavigation() {
        for (const dot of dots) {
            dot.addEventListener("click", (event) => {
                event.preventDefault();
                event.stopPropagation();

                const step = Number(dot.dataset.stepjump);

                if (!Number.isFinite(step)) {
                    return;
                }

                setActiveStep(step);
            });
        }
    }

    /*
        スマホ用の丸矢印ボタンで前後移動する。
    */
    function setupArrowNavigation() {
        arrowPrev?.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();

            goPrev();
        });

        arrowNext?.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();

            goNext();
        });
    }

    /*
        PC用のカード左右クリックエリアで前後移動する。
        スマホ表示時は動作させない。
    */
    function setupPcClickZones() {
        pcZonePrev?.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (!isPcLayout()) {
                return;
            }

            goPrev();
        });

        pcZoneNext?.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (!isPcLayout()) {
                return;
            }

            goNext();
        });
    }

    /*
        スワイプ判定から除外する要素かどうかを判定する。
        ドット・矢印・PCクリックエリア上の操作は、通常のクリック操作として扱う。
    */
    function isSwipeIgnoredTarget(target) {
        return Boolean(
            target.closest(".terms-dot") ||
            target.closest(".terms-arrow") ||
            target.closest(".terms-click-zone"),
        );
    }

    /*
        スマホの横スワイプ操作を設定する。
        縦方向の動きが大きい場合は、通常スクロールとして扱う。
    */
    function setupSwipeNavigation() {
        let touchStartX = 0;
        let touchStartY = 0;
        let isSwiping = false;

        viewport.addEventListener(
            "touchstart",
            (event) => {
                if (isSwipeIgnoredTarget(event.target)) {
                    return;
                }

                const touch = event.touches[0];

                if (!touch) {
                    return;
                }

                touchStartX = touch.clientX;
                touchStartY = touch.clientY;
                isSwiping = true;
            },
            { passive: true },
        );

        viewport.addEventListener(
            "touchmove",
            (event) => {
                if (!isSwiping) {
                    return;
                }

                const touch = event.touches[0];

                if (!touch) {
                    return;
                }

                const diffX = Math.abs(touch.clientX - touchStartX);
                const diffY = Math.abs(touch.clientY - touchStartY);

                if (diffY > diffX) {
                    isSwiping = false;
                    return;
                }

                if (diffX > 10) {
                    event.preventDefault();
                }
            },
            { passive: false },
        );

        viewport.addEventListener(
            "touchend",
            (event) => {
                if (!isSwiping) {
                    return;
                }

                isSwiping = false;

                const touch = event.changedTouches[0];

                if (!touch) {
                    return;
                }

                const diffX = touchStartX - touch.clientX;
                const swipeThreshold = 50;

                if (Math.abs(diffX) < swipeThreshold) {
                    return;
                }

                if (diffX > 0) {
                    goNext();
                    return;
                }

                goPrev();
            },
            { passive: true },
        );
    }

    /*
        入力・クリック操作中に、左右キー移動が割り込まないようにする。
    */
    function isKeyboardIgnoredTarget(target) {
        return Boolean(target.closest("input, textarea, select, button, a"));
    }

    /*
        PC表示時だけ、左右キーでステップ移動できるようにする。
        viewport 内にフォーカスがある時だけ動作させる。
    */
    function setupKeyboardNavigation() {
        document.addEventListener("keydown", (event) => {
            if (!isPcLayout()) {
                return;
            }

            if (event.key !== "ArrowRight" && event.key !== "ArrowLeft") {
                return;
            }

            if (!viewport.contains(event.target)) {
                return;
            }

            if (isKeyboardIgnoredTarget(event.target)) {
                return;
            }

            event.preventDefault();

            if (event.key === "ArrowRight") {
                goNext();
                return;
            }

            goPrev();
        });
    }

    /*
        同意フォーム送信後は、確認済みステップ情報を削除する。
        次回表示時に古い確認状態が残らないようにするため。
    */
    function setupAgreeFormCleanup() {
        if (mode !== "confirm" || !agreeBtn) {
            return;
        }

        const form = agreeBtn.closest("form");

        if (!form) {
            return;
        }

        form.addEventListener("submit", () => {
            try {
                sessionStorage.removeItem(STORAGE_KEY);
            } catch {
                // sessionStorage が使えない環境では何もしない
            }
        });
    }
});
