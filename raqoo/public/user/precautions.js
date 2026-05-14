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
        - サイドバーのハンバーガーメニュー処理は書かない
        - サイドバー開閉は home.js などの共通JS側で扱う
    */


    /* =========================================
       01. 初期値・DOM要素取得
    ========================================= */
    const mode = document.body.dataset.mode;
    const startStep = Number(document.body.dataset.startStep || 1);

    const viewport = document.querySelector(".terms-viewport");
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

    let currentStep = Math.min(Math.max(startStep, 1), TOTAL);


    /* =========================================
       02. PC判定
       - 769px以上をPC扱い
       - CSS側の media query と合わせる
    ========================================= */
    const isPC = () => window.matchMedia("(min-width: 769px)").matches;


    /* =========================================
       03. 訪問済みステップの読み込み・保存
       - confirm モード時のみ使用
       - sessionStorage に確認済みステップを保存する
    ========================================= */
    const loadVisited = () => {
        try {
            const raw = sessionStorage.getItem(STORAGE_KEY);

            if (!raw) {
                return new Set();
            }

            const arr = JSON.parse(raw);

            if (!Array.isArray(arr)) {
                return new Set();
            }

            return new Set(
                arr
                    .map((number) => Number(number))
                    .filter((number) => Number.isFinite(number))
            );
        } catch {
            return new Set();
        }
    };

    const saveVisited = (set) => {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(set)));
        } catch {
            // sessionStorage が使えない環境では保存せずに続行する
        }
    };

    const visited = mode === "confirm" ? loadVisited() : new Set();


    /* =========================================
       04. 同意ボタンの有効 / 無効を更新
       - 全ステップを確認したら押せるようにする
    ========================================= */
    const updateAgree = () => {
        if (mode !== "confirm" || !agreeBtn) {
            return;
        }

        const isAllVisited = visited.size >= TOTAL;

        agreeBtn.disabled = !isAllVisited;
        agreeBtn.setAttribute("aria-disabled", isAllVisited ? "false" : "true");
    };


    /* =========================================
       05. 移動要素の表示 / 非表示を更新
       - data-hidden だけでなく hidden / aria-hidden / tabindex も更新する
       - 非表示要素にTabフォーカスが当たらないようにする
    ========================================= */
    const setMoveElementVisibility = (element, isHidden, useDisabled = false) => {
        if (!element) {
            return;
        }

        element.dataset.hidden = isHidden ? "true" : "false";
        element.hidden = isHidden;
        element.setAttribute("aria-hidden", isHidden ? "true" : "false");

        if (useDisabled) {
            element.disabled = isHidden;
        }

        if (isHidden) {
            element.setAttribute("tabindex", "-1");
        } else {
            element.removeAttribute("tabindex");
        }
    };


    /* =========================================
       06. 左右移動UIの表示 / 非表示を更新
       - STEP1では左移動を隠す
       - 最終STEPでは右移動を隠す
       - スマホ用矢印とPC用クリックエリアの両方を制御する
    ========================================= */
    const updateMoveZones = () => {
        const isFirst = currentStep === 1;
        const isLast = currentStep === TOTAL;

        // スマホ用：丸矢印ボタン
        setMoveElementVisibility(arrowPrev, isFirst, true);
        setMoveElementVisibility(arrowNext, isLast, true);

        // PC用：カード左右クリックエリア
        setMoveElementVisibility(pcZonePrev, isFirst, false);
        setMoveElementVisibility(pcZoneNext, isLast, false);
    };


    /* =========================================
       07. 表示中カード内の操作要素へフォーカスを移す
       - キーボード操作時の見失い防止
    ========================================= */
    const focusActiveStep = (activeCard) => {
        if (!activeCard) {
            return;
        }

        const focusTarget = activeCard.querySelector(
            ".terms-dot[data-active='true'], button:not([disabled]), a, input, select, textarea, [tabindex]:not([tabindex='-1'])"
        );

        focusTarget?.focus?.({ preventScroll: true });
    };


    /* =========================================
       08. 指定ステップを表示
       - カード
       - ドット
       - スマホ用矢印
       - PC用クリックエリア
       - 同意ボタン状態
       をまとめて更新する
    ========================================= */
    const setActive = (step, options = {}) => {
        if (step < 1 || step > TOTAL) {
            return;
        }

        const shouldFocus = options.focus !== false;
        currentStep = step;

        let activeCard = null;

        cards.forEach((card) => {
            const isActive = Number(card.dataset.step) === step;

            card.dataset.active = isActive ? "true" : "false";
            card.style.display = isActive ? "" : "none";

            if (isActive) {
                activeCard = card;
            }
        });

        dots.forEach((dot) => {
            const isActive = Number(dot.dataset.stepjump) === step;

            dot.dataset.active = isActive ? "true" : "false";
        });

        updateMoveZones();

        if (mode === "confirm") {
            visited.add(step);
            saveVisited(visited);
            updateAgree();
        }

        if (shouldFocus) {
            focusActiveStep(activeCard);
        }
    };


    /* =========================================
       09. 初期表示
    ========================================= */
    if (mode === "confirm") {
        visited.add(currentStep);
        saveVisited(visited);
        updateAgree();
    }

    setActive(currentStep, { focus: false });


    /* =========================================
       10. 前へ / 次へ移動
       - 端では循環せずに止める
       - STEP1で前へ、最終STEPで次へは何もしない
    ========================================= */
    const goNext = () => {
        if (currentStep >= TOTAL) {
            return;
        }

        setActive(currentStep + 1);
    };

    const goPrev = () => {
        if (currentStep <= 1) {
            return;
        }

        setActive(currentStep - 1);
    };


    /* =========================================
       11. スワイプ操作
       - 左スワイプ：次のステップ
       - 右スワイプ：前のステップ
       - 縦方向の動きが大きい場合は通常スクロール扱い
    ========================================= */
    let touchStartX = 0;
    let touchStartY = 0;
    let isSwiping = false;

    if (viewport) {
        viewport.addEventListener("touchstart", (event) => {
            /*
               ドット・スマホ用矢印・PC用クリックエリア上の操作は
               スワイプ判定から除外する
            */
            if (
                event.target.closest(".terms-dot") ||
                event.target.closest(".terms-arrow") ||
                event.target.closest(".terms-click-zone")
            ) {
                return;
            }

            const touch = event.touches[0];

            touchStartX = touch.clientX;
            touchStartY = touch.clientY;
            isSwiping = true;
        }, { passive: true });

        viewport.addEventListener("touchmove", (event) => {
            if (!isSwiping) {
                return;
            }

            const dx = Math.abs(event.touches[0].clientX - touchStartX);
            const dy = Math.abs(event.touches[0].clientY - touchStartY);

            if (dy > dx) {
                isSwiping = false;
                return;
            }

            if (dx > 10) {
                event.preventDefault();
            }
        }, { passive: false });

        viewport.addEventListener("touchend", (event) => {
            if (!isSwiping) {
                return;
            }

            isSwiping = false;

            const touchEndX = event.changedTouches[0].clientX;
            const diff = touchStartX - touchEndX;
            const threshold = 50;

            if (Math.abs(diff) < threshold) {
                return;
            }

            if (diff > 0) {
                goNext();
            } else {
                goPrev();
            }
        }, { passive: true });
    }


    /* =========================================
       12. キーボード操作
       - PCのみ有効
       - terms-viewport 内にフォーカスがある時だけ動作
       - フォーム要素やボタン操作中は邪魔しない
    ========================================= */
    document.addEventListener("keydown", (event) => {
        if (!isPC()) {
            return;
        }

        if (event.key !== "ArrowRight" && event.key !== "ArrowLeft") {
            return;
        }

        const target = event.target;

        if (!viewport || !viewport.contains(target)) {
            return;
        }

        if (target.closest("input, textarea, select, button, a")) {
            return;
        }

        event.preventDefault();

        if (event.key === "ArrowRight") {
            goNext();
        }

        if (event.key === "ArrowLeft") {
            goPrev();
        }
    });


    /* =========================================
       13. ドットクリック
       - 指定したステップへ直接移動
    ========================================= */
    dots.forEach((dot) => {
        dot.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();

            const step = Number(dot.dataset.stepjump);

            if (Number.isFinite(step)) {
                setActive(step);
            }
        });
    });


    /* =========================================
       14. スマホ用：丸矢印ボタンクリック
       - 左矢印：前へ
       - 右矢印：次へ
    ========================================= */
    if (arrowPrev) {
        arrowPrev.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();

            goPrev();
        });
    }

    if (arrowNext) {
        arrowNext.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();

            goNext();
        });
    }


    /* =========================================
       15. PC用：カード左右クリックエリア
       - 左半分クリック：前へ
       - 右半分クリック：次へ
       - PC表示時だけ動作
    ========================================= */
    if (pcZonePrev) {
        pcZonePrev.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (!isPC()) {
                return;
            }

            goPrev();
        });
    }

    if (pcZoneNext) {
        pcZoneNext.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (!isPC()) {
                return;
            }

            goNext();
        });
    }


    /* =========================================
       16. 同意フォーム送信時の後処理
       - 確認済みステップ情報を sessionStorage から削除
    ========================================= */
    if (mode === "confirm" && agreeBtn) {
        const form = agreeBtn.closest("form");

        if (form) {
            form.addEventListener("submit", () => {
                try {
                    sessionStorage.removeItem(STORAGE_KEY);
                } catch {
                    // sessionStorage が使えない環境では何もしない
                }
            });
        }
    }
});