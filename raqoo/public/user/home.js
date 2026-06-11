document.addEventListener("DOMContentLoaded", () => {
    /*
        home.js
        役割：
        - 日付タブの表示・切り替え
        - 選択日の予約状況取得
        - 予約カードのリンク / ステータス更新
        - スマホ用「予約状況を見る」シートの表示
        - スマホ / タブレット用サイドバー開閉
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

    const isTabletOrMobile = () => {
        return window.matchMedia("(max-width: 1024px)").matches;
    };

    /* =========================================
       02. サイドバー開閉処理
    ========================================= */
    const toggleButton = document.querySelector(".nav-toggle");
    const navigation = document.querySelector(".navigation");

    const overlay =
        document.querySelector("#mobileOverlay") ||
        document.querySelector("#navBackdrop");

    const closeMenu = () => {
        if (!toggleButton || !navigation) {
            return;
        }

        document.body.classList.remove("nav-open");
        navigation.dataset.open = "false";
        toggleButton.setAttribute("aria-expanded", "false");
    };

    const openMenu = () => {
        if (!toggleButton || !navigation || !isTabletOrMobile()) {
            return;
        }

        document.body.classList.add("nav-open");
        navigation.dataset.open = "true";
        toggleButton.setAttribute("aria-expanded", "true");
    };

    if (toggleButton && navigation) {
        toggleButton.addEventListener("click", (event) => {
            if (!isTabletOrMobile()) {
                return;
            }

            event.stopPropagation();

            if (document.body.classList.contains("nav-open")) {
                closeMenu();
            } else {
                openMenu();
            }
        });

        navigation.addEventListener("click", (event) => {
            event.stopPropagation();
        });

        document.addEventListener("click", (event) => {
            if (!isTabletOrMobile()) {
                return;
            }

            if (!document.body.classList.contains("nav-open")) {
                return;
            }

            if (
                !navigation.contains(event.target) &&
                !toggleButton.contains(event.target)
            ) {
                closeMenu();
            }
        });

        if (overlay) {
            overlay.addEventListener("click", closeMenu);
        }

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                closeMenu();
            }
        });

        window.addEventListener("resize", () => {
            if (!isTabletOrMobile()) {
                closeMenu();
            }
        });
    }

    /* =========================================
       03. DOM要素取得
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
       04. スマホ用予約状況シート
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
       05. ステータス表示用データ
    ========================================= */
    const roomStatusClasses = [
        "available",
        "selecting",
        "pending",
        "reserved",
        "approved",
        "in_use",
        "unavailable",
        "conflict",
        "cancelled",
        "finished",
        "completed",
        "rejected",
        "locked",
    ];

    const roomStatusLabels = {
        available: "利用可能",
        selecting: "選択中",
        pending: "申請中",
        reserved: "予約済み",
        approved: "予約済み",
        in_use: "利用中",
        cancelled: "キャンセル",
        finished: "利用完了",
        completed: "完了",
        rejected: "却下",
        unavailable: "利用不可",
        conflict: "同時刻に予約あり",
    };

    function getRoomStatusLabel(status, statusData = null) {
        if (statusData?.lesson_title) {
            return statusData.lesson_title;
        }

        return roomStatusLabels[status] ?? "利用不可";
    }

    function statusMeta(status) {
        const text = roomStatusLabels[status] ?? status ?? "不明";

        switch (status) {
            case "pending":
                return { text, cls: "my-badge pending" };

            case "reserved":
            case "approved":
                return { text: "予約済み", cls: "my-badge reserved" };

            case "in_use":
                return { text, cls: "my-badge in_use" };

            case "cancelled":
                return { text, cls: "my-badge cancelled" };

            case "finished":
            case "completed":
                return { text, cls: "my-badge completed" };

            case "rejected":
                return { text, cls: "my-badge rejected" };

            case "selecting":
                return { text, cls: "my-badge selecting" };

            default:
                return { text, cls: "my-badge other" };
        }
    }

    /* =========================================
       06. 時限ごとの時間表
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
       07. 自分の予約状況シートを描画
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

            if (!response.ok) {
                throw new Error("予約情報の取得に失敗しました。");
            }

            const reservations = await response.json();

            const priority = (status) => {
                if (status === "reserved" || status === "approved") {
                    return 0;
                }

                if (status === "pending") {
                    return 1;
                }

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
                const isReserved = reservation.status === "reserved" || reservation.status === "approved";

                const card = document.createElement("div");
                card.className = `my-card ${isReserved ? "is-approved" : ""}`;

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

                if (reservation.detail_url) {
                    const detailLink = document.createElement("a");
                    detailLink.className = "my-detail";
                    detailLink.textContent = "詳細を見る";
                    detailLink.href = reservation.detail_url;

                    actions.appendChild(detailLink);
                }

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
       08. スマホ用予約状況シートのイベント設定
    ========================================= */
    openSheetBtn?.addEventListener("click", () => {
        if (!isMobile560()) {
            return;
        }

        const iso = dateInput?.value ? dateInput.value : toISO(new Date());

        renderMyReservationsSheet(iso);
        openSheet();
    });

    closeSheetBtn?.addEventListener("click", closeSheet);

    if (isMobile560() && location.search.includes("date=")) {
        setTimeout(() => {
            const iso = dateInput?.value ? dateInput.value : toISO(new Date());

            renderMyReservationsSheet(iso);
            openSheet();
        }, 250);
    }

    /* =========================================
       09. 初期表示する日付を決定
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
       10. 中央の日付表示を更新
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
       11. 予約リンク内の日付を書き換える
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
       12. 教室カードの予約リンク日付だけ更新する
       - 表示ラベルはここでは触らない
       - API失敗時に「読み込み中...」で止まる問題を防ぐ
    ========================================= */
    function updateRoomCardLinks(date) {
        document.querySelectorAll(".room-group .time-card").forEach((card) => {
            const reserveBtn = card.querySelector(".reserve-btn") || card.querySelector(".sp-reserve-btn");

            let href =
                reserveBtn?.getAttribute("href") ||
                reserveBtn?.getAttribute("data-href") ||
                card.dataset.baseHref;

            if (!href || typeof href !== "string") {
                return;
            }

            href = rewriteReservationUrl(href, date);

            if (reserveBtn?.hasAttribute("href")) {
                reserveBtn.setAttribute("href", href);
            }

            if (reserveBtn?.hasAttribute("data-href")) {
                reserveBtn.setAttribute("data-href", href);
            }

            card.dataset.baseHref = href;

            if (card.hasAttribute("href")) {
                card.setAttribute("href", href);
            }
        });
    }

    /* =========================================
       13. 利用可能な教室カードの表示に切り替える
    ========================================= */
    function applyAvailableStatus(card) {
        if (card.dataset.baseHref) {
            card.setAttribute("href", card.dataset.baseHref);
        }

        card.classList.remove("locked");
        card.removeAttribute("aria-disabled");

        const availableLabels = card.querySelectorAll(".available-label");
        const unavailableLabels = card.querySelectorAll(".unavailable-label");
        const reserveButtons = card.querySelectorAll(".reserve-btn, .sp-reserve-btn");

        availableLabels.forEach((label) => {
            label.classList.remove("hidden");
            label.textContent = "利用可能";
        });

        unavailableLabels.forEach((label) => {
            label.classList.add("hidden");
        });

        reserveButtons.forEach((button) => {
            button.classList.remove("hidden");
        });
    }

    /* =========================================
       14. 利用不可な教室カードの表示に切り替える
    ========================================= */
    function applyUnavailableStatus(card, status, statusData) {
        card.removeAttribute("href");
        card.classList.add("locked");
        card.setAttribute("aria-disabled", "true");

        const label = getRoomStatusLabel(status, statusData);

        const availableLabels = card.querySelectorAll(".available-label");
        const unavailableLabels = card.querySelectorAll(".unavailable-label");
        const reserveButtons = card.querySelectorAll(".reserve-btn, .sp-reserve-btn");

        availableLabels.forEach((labelElement) => {
            labelElement.classList.add("hidden");
        });

        unavailableLabels.forEach((labelElement) => {
            labelElement.classList.remove("hidden");
            labelElement.textContent = label;
        });

        reserveButtons.forEach((button) => {
            button.classList.add("hidden");
        });
    }

    /* =========================================
       15. 選択日の教室予約状況を取得して反映
    ========================================= */
    function updateRoomStatuses(date) {
        const roomGroups = document.querySelectorAll(".room-group");

        if (!roomGroups.length) {
            return;
        }

        updateRoomCardLinks(date);

        fetch(`/user/room-status?date=${encodeURIComponent(date)}`, {
            cache: "no-store",
        })
            .then(async (response) => {
                if (!response.ok) {
                    const errorText = await response.text();

                    console.error("教室予約状況APIエラー:", response.status);
                    console.error("教室予約状況APIレスポンス:", errorText);

                    throw new Error("教室予約状況の取得に失敗しました。");
                }

                return response.json();
            })
            .then((data) => {
                const statusList = Array.isArray(data)
                    ? data
                    : data.statuses ?? data.data ?? [];

                if (!Array.isArray(statusList)) {
                    console.error("教室予約状況APIの形式が不正です:", data);
                    throw new Error("教室予約状況APIの形式が不正です。");
                }

                console.log("教室予約状況API:", statusList);

                roomGroups.forEach((roomGroup) => {
                    const roomId = roomGroup.dataset.roomId;

                    roomGroup.querySelectorAll(".time-card").forEach((card) => {
                        const period = Number(card.dataset.period);

                        const statusData = statusList.find((item) => {
                            const itemRoomId = item.room_id ?? item.roomId;
                            const itemPeriod = item.period ?? item.period_id;

                            return itemRoomId == roomId && Number(itemPeriod) === period;
                        });

                        const status = statusData?.status ?? "available";

                        card.classList.remove(...roomStatusClasses);
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
                /*
                    API失敗時はBlade初期表示を残す。
                    ここで「読み込み中...」などへ上書きしない。
                */
                console.error("ステータス取得エラー:", error);
            });
    }

    /* =========================================
       16. 日付タブを描画
    ========================================= */
    function renderStrip() {
        if (!tabs.length) {
            return;
        }

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

            const isActive =
                date.getFullYear() === selectedDate.getFullYear() &&
                date.getMonth() === selectedDate.getMonth() &&
                date.getDate() === selectedDate.getDate();

            tab.classList.toggle("active", isActive);
        });
    }

    /* =========================================
       17. 初期描画
    ========================================= */
    updateCenterDisplay(selectedDate);
    renderStrip();

    /* =========================================
       18. 日付タブクリック時の処理
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
       19. 前日・翌日ボタンの処理
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
       20. 日付入力欄を変更した時の処理
    ========================================= */
    dateInput?.addEventListener("change", () => {
        const [year, month, day] = dateInput.value.split("-").map(Number);
        const date = new Date(year, month - 1, day);

        if (Number.isNaN(date.getTime())) {
            return;
        }

        selectedDate = date;
        startDate = new Date(selectedDate.getTime() - selectedDate.getDay() * 86400000);

        updateCenterDisplay(selectedDate);
        renderStrip();
    });
});