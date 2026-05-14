document.addEventListener("DOMContentLoaded", () => {
    /*
        home.js
        役割：
        - 日付タブの表示・切り替え
        - 選択日の予約状況取得
        - 予約カードのリンク / ステータス更新
        - スマホ用「予約状況を見る」シートの表示
    */


    /* =========================================
       01. 共通関数
    ========================================= */
    const jaWeek = ["日", "月", "火", "水", "木", "金", "土"];

    const pad2 = (number) => {
        return String(number).padStart(2, "0");
    };

    const toISO = (date) => {
        return `${date.getFullYear()}-${pad2(date.getMonth() + 1)}-${pad2(date.getDate())}`;
    };

    const toMMDD = (date) => {
        return `${pad2(date.getMonth() + 1)}/${pad2(date.getDate())}`;
    };

    const isMobile560 = () => {
        return window.matchMedia("(max-width: 560px)").matches;
    };


    /* =========================================
       02. DOM要素取得
    ========================================= */
    const mainEl = document.querySelector(".date-main");
    const wdayEl = document.querySelector(".date-weekday");
    const dateInput = document.querySelector("#dateInput");

    const tabs = document.querySelectorAll(".day-tabs .day-tab");
    const prevBtn = document.querySelector(".strip-prev");
    const nextBtn = document.querySelector(".strip-next");

    const openSheetBtn = document.querySelector("#openStatusSheet");
    const closeSheetBtn = document.querySelector("#closeStatusSheet");
    const sheet = document.querySelector("#statusSheet");
    const sheetBody = document.querySelector("#statusSheetBody");


    /* =========================================
       03. スマホ用予約状況シート
    ========================================= */
    const openSheet = () => {
        document.body.classList.add("sheet-open");
        sheet?.setAttribute("aria-hidden", "false");
    };

    const closeSheet = () => {
        document.body.classList.remove("sheet-open");
        sheet?.setAttribute("aria-hidden", "true");
    };


    /* =========================================
       04. ステータス表示用データ
    ========================================= */
    function statusMeta(status) {
        switch (status) {
            case "pending":
                return { text: "申請中", cls: "my-badge pending" };

            case "reserved":
                return { text: "予約済み", cls: "my-badge reserved" };

            case "in_use":
                return { text: "利用中", cls: "my-badge in_use" };

            case "finished":
                return { text: "利用完了", cls: "my-badge completed" };

            case "cancelled":
                return { text: "キャンセル", cls: "my-badge cancelled" };

            case "rejected":
                return { text: "却下", cls: "my-badge rejected" };

            case "approved":
                return { text: "予約済み", cls: "my-badge reserved" };

            case "completed":
                return { text: "完了", cls: "my-badge completed" };

            default:
                return { text: status || "不明", cls: "my-badge other" };
        }
    }


    /* =========================================
       05. 時限ごとの時間表
    ========================================= */
    const timeTable = {
        1: ["09:10", "10:40"],
        2: ["10:50", "12:20"],
        3: ["13:00", "14:30"],
        4: ["14:40", "16:10"],
        5: ["16:20", "17:50"],
        6: ["18:00", "19:30"],
    };


    /* =========================================
       06. 自分の予約状況シートを描画
       - 指定日の自分の予約情報を取得
       - スマホ用シート内にカード形式で表示
    ========================================= */
    async function renderMyReservationsSheet(dateISO) {
        if (!sheetBody) {
            return;
        }

        sheetBody.textContent = "読み込み中...";

        try {
            const response = await fetch(`/user/my-reservations?date=${encodeURIComponent(dateISO)}`, {
                cache: "no-store",
            });

            const reservations = await response.json();

            const priority = (status) => {
                if (status === "reserved" || status === "approved") return 0;
                if (status === "pending") return 1;
                return 9;
            };

            reservations.sort((a, b) => {
                const priorityA = priority(a.status);
                const priorityB = priority(b.status);

                if (priorityA !== priorityB) {
                    return priorityA - priorityB;
                }

                if (a.date === b.date) {
                    return (a.period || 0) - (b.period || 0);
                }

                return String(a.date).localeCompare(String(b.date));
            });

            sheetBody.innerHTML = "";

            if (!reservations.length) {
                const emptyDiv = document.createElement("div");
                emptyDiv.className = "my-empty";
                emptyDiv.textContent = "この日の予約はありません";
                sheetBody.appendChild(emptyDiv);
                return;
            }

            reservations.forEach((reservation) => {
                const meta = statusMeta(reservation.status);
                const times = timeTable[reservation.period] || ["--:--", "--:--"];
                const isApproved = reservation.status === "reserved" || reservation.status === "approved";

                const card = document.createElement("div");
                card.className = `my-card ${isApproved ? "is-approved" : ""}`;

                const top = document.createElement("div");
                top.className = "my-top";

                const roomName = document.createElement("div");
                roomName.className = "my-room";
                roomName.textContent = reservation.room_name || "教室";

                const statusBadge = document.createElement("span");
                statusBadge.className = meta.cls;
                statusBadge.textContent = meta.text;

                top.appendChild(roomName);
                top.appendChild(statusBadge);

                const mid = document.createElement("div");
                mid.className = "my-mid";

                const dateItem = document.createElement("div");
                dateItem.className = "my-item";

                const dateKey = document.createElement("div");
                dateKey.className = "my-k";
                dateKey.textContent = "日付";

                const dateVal = document.createElement("div");
                dateVal.className = "my-v";
                dateVal.textContent = reservation.date;

                dateItem.appendChild(dateKey);
                dateItem.appendChild(dateVal);

                const periodItem = document.createElement("div");
                periodItem.className = "my-item";

                const periodKey = document.createElement("div");
                periodKey.className = "my-k";
                periodKey.textContent = "時限";

                const periodVal = document.createElement("div");
                periodVal.className = "my-v";
                periodVal.appendChild(document.createTextNode(`${reservation.period}限 `));

                const timeSpan = document.createElement("span");
                timeSpan.className = "my-time";
                timeSpan.textContent = `${times[0]}-${times[1]}`;

                periodVal.appendChild(timeSpan);
                periodItem.appendChild(periodKey);
                periodItem.appendChild(periodVal);

                mid.appendChild(dateItem);
                mid.appendChild(periodItem);

                const actions = document.createElement("div");
                actions.className = "my-actions";

                const detailLink = document.createElement("a");
                detailLink.className = "my-detail";
                detailLink.textContent = "詳細を見る";
                detailLink.href = reservation.detail_url;

                actions.appendChild(detailLink);

                card.appendChild(top);
                card.appendChild(mid);
                card.appendChild(actions);

                sheetBody.appendChild(card);
            });
        } catch (error) {
            console.error(error);

            sheetBody.innerHTML = "";

            const errorDiv = document.createElement("div");
            errorDiv.className = "my-empty";
            errorDiv.textContent = "取得に失敗しました";

            sheetBody.appendChild(errorDiv);
        }
    }


    /* =========================================
       07. スマホ用予約状況シートのイベント設定
       - 560px以下の画面だけで使用
    ========================================= */
    if (isMobile560()) {
        openSheetBtn?.addEventListener("click", () => {
            const iso = dateInput?.value ? dateInput.value : toISO(new Date());

            renderMyReservationsSheet(iso);
            openSheet();
        });

        closeSheetBtn?.addEventListener("click", closeSheet);

        if (location.search.includes("date=")) {
            setTimeout(() => {
                const iso = dateInput?.value ? dateInput.value : toISO(new Date());

                renderMyReservationsSheet(iso);
                openSheet();
            }, 250);
        }
    }


    /* =========================================
       08. 初期表示する日付を決定
       - date input に値があればそれを使用
       - なければ今日の日付を使用
    ========================================= */
    let selectedDate;

    if (dateInput && dateInput.value) {
        const [year, month, day] = dateInput.value.split("-").map(Number);
        selectedDate = new Date(year, month - 1, day);
    } else {
        selectedDate = new Date();
    }

    let startDate = new Date(selectedDate.getTime() - selectedDate.getDay() * 86400000);


    /* =========================================
       09. 中央の日付表示を更新
       - 表示日付
       - hiddenのdate input
       - 教室カードの予約状況
       - スマホ用予約状況シート
    ========================================= */
    function updateCenterDisplay(date) {
        if (mainEl) {
            mainEl.textContent = toMMDD(date);
        }

        if (wdayEl) {
            wdayEl.textContent = `（${jaWeek[date.getDay()]}）`;
        }

        if (dateInput) {
            dateInput.value = toISO(date);
        }

        updateRoomStatuses(toISO(date));

        if (document.body.classList.contains("sheet-open") && isMobile560()) {
            renderMyReservationsSheet(toISO(date));
        }
    }


    /* =========================================
       10. 予約リンク内の日付を書き換える
       - 注意事項ページ経由の next パラメータにも対応
       - URLとして解析できない場合は正規表現で補正
    ========================================= */
    function rewriteReservationUrl(href, date) {
        let nextHref = href;

        try {
            const url = new URL(nextHref, window.location.origin);

            if (url.searchParams.has("next")) {
                const nextParam = url.searchParams.get("next");
                const nextUrl = new URL(nextParam, window.location.origin);

                if (nextUrl.searchParams.has("date")) {
                    nextUrl.searchParams.set("date", date);
                    url.searchParams.set("next", nextUrl.pathname + nextUrl.search);
                }
            } else if (url.searchParams.has("date")) {
                url.searchParams.set("date", date);
            }

            nextHref = nextHref.startsWith("http") ? url.href : url.pathname + url.search;
        } catch (error) {
            console.warn("URLパースエラー。フォールバックとして正規表現を使用します", error);

            nextHref = nextHref.replace(/date%3D\d{4}-\d{2}-\d{2}/g, `date%3D${date}`);
            nextHref = nextHref.replace(/date=\d{4}-\d{2}-\d{2}/g, `date=${date}`);
        }

        return nextHref;
    }


    /* =========================================
       11. 予約状況取得前に教室カードを初期化
       - 前回のステータスclassを削除
       - ラベルを「読み込み中...」に戻す
       - 予約リンクの日付も更新する
    ========================================= */
    function resetRoomCards(date) {
        document.querySelectorAll(".room-group").forEach((roomGroup) => {
            roomGroup.querySelectorAll(".time-card").forEach((card) => {
                card.classList.remove("available", "pending", "reserved", "in_use", "unavailable", "locked");

                const availableLabel = card.querySelector(".available-label");
                const unavailableLabel = card.querySelector(".unavailable-label");
                const reserveBtn = card.querySelector(".reserve-btn");

                if (availableLabel) {
                    availableLabel.classList.add("hidden");
                }

                if (unavailableLabel) {
                    unavailableLabel.classList.remove("hidden");
                    unavailableLabel.textContent = "読み込み中...";
                }

                if (reserveBtn) {
                    reserveBtn.classList.add("hidden");
                }

                if (reserveBtn) {
                    let href = reserveBtn.getAttribute("href") ||
                        reserveBtn.getAttribute("data-href") ||
                        card.dataset.baseHref;

                    if (href && typeof href === "string") {
                        href = rewriteReservationUrl(href, date);

                        if (reserveBtn.hasAttribute("href")) {
                            reserveBtn.setAttribute("href", href);
                        }

                        if (reserveBtn.hasAttribute("data-href")) {
                            reserveBtn.setAttribute("data-href", href);
                        }

                        card.dataset.baseHref = href;

                        if (card.hasAttribute("href")) {
                            card.setAttribute("href", href);
                        }
                    }
                }
            });
        });
    }


    /* =========================================
       12. 利用可能な教室カードの表示に切り替える
       - 予約リンクを有効化
       - 利用可能ラベルと予約ボタンを表示
    ========================================= */
    function applyAvailableStatus(card) {
        card.setAttribute("href", card.dataset.baseHref);
        card.classList.remove("locked");
        card.removeAttribute("aria-disabled");

        const availableLabel = card.querySelector(".pc-layout .available-label");
        const unavailableLabel = card.querySelector(".pc-layout .unavailable-label");
        const reserveBtn = card.querySelector(".reserve-btn.pc-only");

        const spAvailableLabel = card.querySelector(".sp-layout .available-label");
        const spUnavailableLabel = card.querySelector(".sp-layout .unavailable-label");
        const spReserveBtn = card.querySelector(".sp-reserve-btn");

        if (availableLabel) {
            availableLabel.classList.remove("hidden");
        }

        if (unavailableLabel) {
            unavailableLabel.classList.add("hidden");
        }

        if (reserveBtn) {
            reserveBtn.classList.remove("hidden");
        }

        if (spAvailableLabel) {
            spAvailableLabel.classList.remove("hidden");
        }

        if (spUnavailableLabel) {
            spUnavailableLabel.classList.add("hidden");
        }

        if (spReserveBtn) {
            spReserveBtn.classList.remove("hidden");
        }
    }


    /* =========================================
       13. 利用不可な教室カードの表示に切り替える
       - 予約リンクを無効化
       - 利用不可ラベルを表示
       - 予約ボタンを非表示
    ========================================= */
    function applyUnavailableStatus(card, status, statusData) {
        card.removeAttribute("href");
        card.classList.add("locked");
        card.setAttribute("aria-disabled", "true");

        const statusLabels = {
            pending: "申請中",
            reserved: "予約済み",
            in_use: "利用中",
            unavailable: "利用不可",
        };

        const label = statusData?.lesson_title ?? statusLabels[status] ?? "利用不可";

        const availableLabel = card.querySelector(".pc-layout .available-label");
        const unavailableLabel = card.querySelector(".pc-layout .unavailable-label");
        const reserveBtn = card.querySelector(".reserve-btn.pc-only");

        const spAvailableLabel = card.querySelector(".sp-layout .available-label");
        const spUnavailableLabel = card.querySelector(".sp-layout .unavailable-label");
        const spReserveBtn = card.querySelector(".sp-reserve-btn");

        if (availableLabel) {
            availableLabel.classList.add("hidden");
        }

        if (unavailableLabel) {
            unavailableLabel.classList.remove("hidden");
            unavailableLabel.textContent = label;
        }

        if (reserveBtn) {
            reserveBtn.classList.add("hidden");
        }

        if (spAvailableLabel) {
            spAvailableLabel.classList.add("hidden");
        }

        if (spUnavailableLabel) {
            spUnavailableLabel.classList.remove("hidden");
            spUnavailableLabel.textContent = label;
        }

        if (spReserveBtn) {
            spReserveBtn.classList.add("hidden");
        }
    }


    /* =========================================
       14. 選択日の教室予約状況を取得して反映
       - /user/room-status から予約状況を取得
       - 教室IDと時限を照合してカードへ反映
    ========================================= */
    function updateRoomStatuses(date) {
        resetRoomCards(date);

        fetch(`/user/room-status?date=${date}`, { cache: "no-store" })
            .then((response) => response.json())
            .then((data) => {
                document.querySelectorAll(".room-group").forEach((roomGroup) => {
                    const roomId = roomGroup.dataset.roomId;

                    roomGroup.querySelectorAll(".time-card").forEach((card) => {
                        const period = Number(card.dataset.period);
                        const statusData = data.find((item) => {
                            return item.room_id == roomId && Number(item.period) === period;
                        });

                        const status = statusData ? statusData.status : "available";

                        card.classList.remove("available", "pending", "reserved", "in_use", "unavailable", "locked");
                        card.classList.add(status);

                        if (status === "available") {
                            applyAvailableStatus(card);
                        } else {
                            applyUnavailableStatus(card, status, statusData);
                        }
                    });
                });
            })
            .catch((error) => {
                console.error("ステータス取得エラー:", error);
            });
    }


    /* =========================================
       15. 日付タブを描画
       - 開始日から7日分を表示
       - 選択中の日付には active を付ける
    ========================================= */
    function renderStrip() {
        tabs.forEach((tab, index) => {
            const date = new Date(startDate.getTime() + index * 86400000);

            const dayLabel = tab.querySelector(".day-label");
            const dateLabel = tab.querySelector(".date-label");

            if (dayLabel) {
                dayLabel.textContent = jaWeek[date.getDay()];
            }

            if (dateLabel) {
                dateLabel.textContent = toMMDD(date);
            }

            tab.dataset.date = toISO(date);

            const isActive = date.getFullYear() === selectedDate.getFullYear() &&
                date.getMonth() === selectedDate.getMonth() &&
                date.getDate() === selectedDate.getDate();

            tab.classList.toggle("active", isActive);
        });
    }


    /* =========================================
       16. 初期描画
    ========================================= */
    updateCenterDisplay(selectedDate);
    renderStrip();


    /* =========================================
       17. 日付タブクリック時の処理
    ========================================= */
    tabs.forEach((tab) => {
        tab.addEventListener("click", () => {
            const iso = tab.dataset.date;

            if (!iso) {
                return;
            }

            const [year, month, day] = iso.split("-").map(Number);

            selectedDate = new Date(year, month - 1, day);
            startDate = new Date(selectedDate.getTime() - selectedDate.getDay() * 86400000);

            updateCenterDisplay(selectedDate);
            renderStrip();
        });
    });


    /* =========================================
       18. 前日・翌日ボタンの処理
    ========================================= */
    prevBtn?.addEventListener("click", () => {
        selectedDate = new Date(selectedDate.getTime() - 86400000);
        startDate = new Date(startDate.getTime() - 86400000);

        updateCenterDisplay(selectedDate);
        renderStrip();
    });

    nextBtn?.addEventListener("click", () => {
        selectedDate = new Date(selectedDate.getTime() + 86400000);
        startDate = new Date(startDate.getTime() + 86400000);

        updateCenterDisplay(selectedDate);
        renderStrip();
    });


    /* =========================================
       19. 日付入力欄を変更した時の処理
    ========================================= */
    dateInput?.addEventListener("change", () => {
        const [year, month, day] = dateInput.value.split("-").map(Number);
        const date = new Date(year, month - 1, day);

        if (isNaN(date)) {
            return;
        }

        selectedDate = date;
        startDate = new Date(selectedDate.getTime() - selectedDate.getDay() * 86400000);

        updateCenterDisplay(selectedDate);
        renderStrip();
    });
});
document.addEventListener('DOMContentLoaded', () => {
    /*
        サイドバー開閉処理
        - 1024px以下でハンバーガーメニューを有効化
        - サイドバー外クリックで閉じる
        - オーバーレイクリックで閉じる
        - ESCキーで閉じる
        - PC幅に戻ったら開閉状態をリセットする
    */
    const toggleButton = document.querySelector('.nav-toggle');
    const navigation = document.querySelector('.navigation');

    /*
        画面によって overlay の id が違う可能性があるため両対応する
        - mobileOverlay: 既存ページで使用
        - navBackdrop: 追加されている場合のみ使用
    */
    const overlay =
        document.querySelector('#mobileOverlay') ||
        document.querySelector('#navBackdrop');

    if (!toggleButton || !navigation) {
        return;
    }

    const isTabletOrMobile = () => {
        return window.matchMedia('(max-width: 1024px)').matches;
    };

    const openMenu = () => {
        if (!isTabletOrMobile()) {
            return;
        }

        document.body.classList.add('nav-open');
        navigation.dataset.open = 'true';
        toggleButton.setAttribute('aria-expanded', 'true');
    };

    const closeMenu = () => {
        document.body.classList.remove('nav-open');
        navigation.dataset.open = 'false';
        toggleButton.setAttribute('aria-expanded', 'false');
    };

    const toggleMenu = (event) => {
        if (!isTabletOrMobile()) {
            return;
        }

        event.stopPropagation();

        if (document.body.classList.contains('nav-open')) {
            closeMenu();
        } else {
            openMenu();
        }
    };

    /*
        ハンバーガーボタンで開閉
    */
    toggleButton.addEventListener('click', toggleMenu);

    /*
        サイドバー内クリックでは閉じない
        例: メニュー内のフォームやリンクを押したときに外側クリック扱いさせない
    */
    navigation.addEventListener('click', (event) => {
        event.stopPropagation();
    });

    /*
        サイドバー外をクリックしたら閉じる
    */
    document.addEventListener('click', (event) => {
        if (!isTabletOrMobile()) {
            return;
        }

        if (!document.body.classList.contains('nav-open')) {
            return;
        }

        if (
            !navigation.contains(event.target) &&
            !toggleButton.contains(event.target)
        ) {
            closeMenu();
        }
    });

    /*
        オーバーレイが存在するページでは、オーバーレイクリックでも閉じる
    */
    if (overlay) {
        overlay.addEventListener('click', closeMenu);
    }

    /*
        ESCキーで閉じる
    */
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenu();
        }
    });

    /*
        PC幅に戻ったら閉じる
    */
    window.addEventListener('resize', () => {
        if (!isTabletOrMobile()) {
            closeMenu();
        }
    });
});