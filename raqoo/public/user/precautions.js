document.addEventListener("DOMContentLoaded", () => {
    /*
        precautions.js
        役割：
        - 注意事項カード STEP1〜STEP3 の切り替え
        - ドット・左右矢印・左右キーでの移動
        - スマホの横スワイプ操作
        - 同意モード時の「全ステップ確認済み」判定

        注意：
        - サイドバーのハンバーガーメニュー処理は書かない
        - サイドバー開閉は共通JS側で扱う
    */


    /* =========================================
       01. 初期値・DOM要素取得
    ========================================= */
    const mode = document.body.dataset.mode;
    const startStep = Number(document.body.dataset.startStep || 1);

    const cards = Array.from(document.querySelectorAll(".terms-card"));
    const dots = Array.from(document.querySelectorAll(".terms-dot"));
    const agreeBtn = document.getElementById("agreeBtn");

    const arrowPrev = document.getElementById("arrowPrev");
    const arrowNext = document.getElementById("arrowNext");

    const TOTAL = cards.length || 3;
    const STORAGE_KEY = "precautions_visited_steps_v1";

    let currentStep = Math.min(Math.max(startStep, 1), TOTAL);


    /* =========================================
       02. 訪問済みステップの読み込み・保存
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
       03. 同意ボタンの有効 / 無効を更新
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
       04. 左右矢印の表示 / 非表示を更新
       - STEP1では左矢印を隠す
       - 最終STEPでは右矢印を隠す
    ========================================= */
    const updateArrows = () => {
        if (arrowPrev) {
            arrowPrev.dataset.hidden = currentStep === 1 ? "true" : "false";
        }

        if (arrowNext) {
            arrowNext.dataset.hidden = currentStep === TOTAL ? "true" : "false";
        }
    };


    /* =========================================
       05. 指定ステップを表示
       - カード
       - ドット
       - 矢印
       - 同意ボタン状態
       をまとめて更新する
    ========================================= */
    const setActive = (step) => {
        currentStep = step;

        cards.forEach((card) => {
            const isActive = Number(card.dataset.step) === step;

            card.dataset.active = isActive ? "true" : "false";
            card.style.display = isActive ? "" : "none";
        });

        dots.forEach((dot) => {
            const isActive = Number(dot.dataset.stepjump) === step;

            dot.dataset.active = isActive ? "true" : "false";
        });

        updateArrows();

        if (mode === "confirm") {
            visited.add(step);
            saveVisited(visited);
            updateAgree();
        }
    };


    /* =========================================
       06. 初期表示
    ========================================= */
    if (mode === "confirm") {
        visited.add(currentStep);
        saveVisited(visited);
        updateAgree();
    }

    setActive(currentStep);


    /* =========================================
       07. 前へ / 次へ移動
       - ここでは循環移動の計算をしている
       - 矢印クリック時は端で止める条件を別途入れている
    ========================================= */
    const goNext = () => {
        setActive((currentStep % TOTAL) + 1);
    };

    const goPrev = () => {
        setActive(((currentStep - 2 + TOTAL) % TOTAL) + 1);
    };


    /* =========================================
       08. スワイプ操作
       - 左スワイプ：次のステップ
       - 右スワイプ：前のステップ
       - 縦方向の動きが大きい場合は通常スクロール扱い
    ========================================= */
    let touchStartX = 0;
    let touchStartY = 0;
    let isSwiping = false;

    const viewport = document.querySelector(".terms-viewport");

    if (viewport) {
        viewport.addEventListener("touchstart", (event) => {
            if (event.target.closest(".terms-dot") || event.target.closest(".terms-arrow")) {
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
       09. キーボード操作
       - 右矢印キー：次へ
       - 左矢印キー：前へ
    ========================================= */
    document.addEventListener("keydown", (event) => {
        if (event.key === "ArrowRight") {
            goNext();
        }

        if (event.key === "ArrowLeft") {
            goPrev();
        }
    });


    /* =========================================
       10. ドットクリック
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
       11. 矢印ボタンクリック
       - 左矢印：前へ
       - 右矢印：次へ
       - 端のステップでは移動しない
    ========================================= */
    if (arrowPrev) {
        arrowPrev.addEventListener("click", (event) => {
            event.preventDefault();

            if (currentStep > 1) {
                goPrev();
            }
        });
    }

    if (arrowNext) {
        arrowNext.addEventListener("click", (event) => {
            event.preventDefault();

            if (currentStep < TOTAL) {
                goNext();
            }
        });
    }


    /* =========================================
       12. 同意フォーム送信時の後処理
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