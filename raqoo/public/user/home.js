document.addEventListener("DOMContentLoaded", () => {
    console.log("[raqoo] home.js loaded: v-complete-2026-06-17");
    /* =========================================
       01. 共通関数
    ========================================= */
    const jaWeek = ["日", "月", "火", "水", "木", "金", "土"];

    const pad2 = (number) => String(number).padStart(2, "0");

    const toISO = (date) =>
        `${date.getFullYear()}-${pad2(date.getMonth() + 1)}-${pad2(date.getDate())}`;

    const toMMDD = (date) => `${pad2(date.getMonth() + 1)}/${pad2(date.getDate())}`;

    const isMobile560 = () => window.matchMedia("(max-width: 560px)").matches;

    const isTabletOrMobile = () => window.matchMedia("(max-width: 1024px)").matches;

    /* =========================================
       02. サイドバー開閉処理
    ========================================= */
    const toggleButton = document.querySelector(".nav-toggle");
    const navigation = document.querySelector(".navigation");

    const overlay =
        document.querySelector("#mobileOverlay") ||
        document.querySelector("#navBackdrop");

    const closeMenu = () => {
        if (!toggleButton || !navigation) return;
        document.body.classList.remove("nav-open");
        navigation.dataset.open = "false";
        toggleButton.setAttribute("aria-expanded", "false");
    };

    const openMenu = () => {
        if (!toggleButton || !navigation || !isTabletOrMobile()) return;
        document.body.classList.add("nav-open");
        navigation.dataset.open = "true";
        toggleButton.setAttribute("aria-expanded", "true");
    };

    if (toggleButton && navigation) {
        toggleButton.addEventListener("click", (event) => {
            if (!isTabletOrMobile()) return;
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
            if (!isTabletOrMobile()) return;
            if (!document.body.classList.contains("nav-open")) return;

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
            if (event.key === "Escape") closeMenu();
        });

        window.addEventListener("resize", () => {
            if (!isTabletOrMobile()) closeMenu();
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

    // ▼ status文字列を正規化する（大文字/前後空白/別名の揺れを吸収）
    function normalizeStatus(rawStatus) {
        if (rawStatus === null || rawStatus === undefined) return "available";

        let status = String(rawStatus).trim().toLowerCase();

        // よくある別名を正規化
        const aliasMap = {
            ok: "available",
            free: "available",
            open: "available",
            empty: "available",
            booked: "reserved",
            reserve: "reserved",
            using: "in_use",
            inuse: "in_use",
            "in-use": "in_use",
            apply: "pending",
            applying: "pending",
            unavailable_room: "unavailable",
        };

        if (aliasMap[status]) status = aliasMap[status];

        // 既知のステータスでなければ「利用不可」を表すクラスへ寄せる
        if (!roomStatusClasses.includes(status)) {
            console.warn("[raqoo] 未知のstatus値を検出。利用不可として扱います:", rawStatus);
            return "unavailable";
        }

        return status;
    }

    function getRoomStatusLabel(status, statusData = null) {
        if (statusData?.lesson_title) return statusData.lesson_title;
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
        if (!sheetBody) return;

        sheetBody.textContent = "読み込み中...";

        try {
            const response = await fetch(
                `/user/my-reservations?date=${encodeURIComponent(dateISO)}`,
                { cache: "no-store", headers: { Accept: "application/json" } }
            );

            if (!response.ok) {
                throw new Error(`予約情報の取得に失敗しました (HTTP ${response.status})`);
            }

            const reservations = await response.json();
            const list = Array.isArray(reservations)
                ? reservations
                : reservations.reservations ?? reservations.data ?? [];

            const priority = (status) => {
                if (status === "reserved" || status === "approved") return 0;
                if (status === "pending") return 1;
                return 9;
            };

            list.sort((a, b) => {
                const priorityA = priority(a.status);
                const priorityB = priority(b.status);
                if (priorityA !== priorityB) return priorityA - priorityB;
                if (a.date === b.date) return (a.period || 0) - (b.period || 0);
                return String(a.date).localeCompare(String(b.date));
            });

            sheetBody.innerHTML = "";

            if (!list.length) {
                const emptyDiv = document.createElement("div");
                emptyDiv.className = "my-empty";
                emptyDiv.textContent = "この日の予約はありません";
                sheetBody.appendChild(emptyDiv);
                return;
            }

            list.forEach((reservation) => {
                const normalized = normalizeStatus(reservation.status);
                const meta = statusMeta(normalized);
                const times = timeTable[reservation.period] || ["--:--", "--:--"];
                const isReserved = normalized === "reserved" || normalized === "approved";

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
            console.error("[raqoo] my-reservations 取得失敗:", error);
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
        if (!isMobile560()) return;
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
        if (mainEl) mainEl.textContent = toMMDD(date);
        if (wdayEl) wdayEl.textContent = `（${jaWeek[date.getDay()]}）`;
        if (dateInput) dateInput.value = toISO(date);

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
            console.warn("[raqoo] URLパースエラー。正規表現でフォールバックします", error);
            nextHref = nextHref.replace(/date%3D\d{4}-\d{2}-\d{2}/g, `date%3D${date}`);
            nextHref = nextHref.replace(/date=\d{4}-\d{2}-\d{2}/g, `date=${date}`);
        }

        return nextHref;
    }

    /* =========================================
       12. 教室カードの予約リンク日付だけ更新する
    ========================================= */
    function updateRoomCardLinks(date) {
        document.querySelectorAll(".room-group .time-card").forEach((card) => {
            const reserveBtn =
                card.querySelector(".reserve-btn") || card.querySelector(".sp-reserve-btn");

            let href =
                reserveBtn?.getAttribute("href") ||
                reserveBtn?.getAttribute("data-href") ||
                card.dataset.baseHref;

            if (!href || typeof href !== "string") return;

            href = rewriteReservationUrl(href, date);

            if (reserveBtn?.hasAttribute("href")) reserveBtn.setAttribute("href", href);
            if (reserveBtn?.hasAttribute("data-href")) reserveBtn.setAttribute("data-href", href);

            card.dataset.baseHref = href;
            if (card.hasAttribute("href")) card.setAttribute("href", href);
        });
    }

    /* =========================================
       13. 利用可能な教室カードの表示に切り替える
    ========================================= */
    function applyAvailableStatus(card) {
        if (card.dataset.baseHref) card.setAttribute("href", card.dataset.baseHref);

        card.classList.remove("locked");
        card.removeAttribute("aria-disabled");

        const availableLabels = card.querySelectorAll(".available-label");
        const unavailableLabels = card.querySelectorAll(".unavailable-label");
        const reserveButtons = card.querySelectorAll(".reserve-btn, .sp-reserve-btn");

        availableLabels.forEach((label) => {
            label.classList.remove("hidden");
            label.textContent = "利用可能";
        });
        unavailableLabels.forEach((label) => label.classList.add("hidden"));
        reserveButtons.forEach((button) => button.classList.remove("hidden"));
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

        availableLabels.forEach((labelElement) => labelElement.classList.add("hidden"));
        unavailableLabels.forEach((labelElement) => {
            labelElement.classList.remove("hidden");
            labelElement.textContent = label;
        });
        reserveButtons.forEach((button) => button.classList.add("hidden"));
    }

    /* =========================================
       14-b. Blade初期表示へ戻す（API失敗時のフォールバック）
       - 「読み込み中...」で固まらないようにするための保険
       - data-* 属性は触らず、ラベル文言は data-init-* があれば復帰
    ========================================= */
    function restoreInitialLabels() {
        document.querySelectorAll(".room-group .time-card").forEach((card) => {
            const unavailableLabels = card.querySelectorAll(".unavailable-label");
            unavailableLabels.forEach((label) => {
                // 「読み込み中...」が残っていたら、安全側の文言に戻す
                if (label.textContent.includes("読み込み中")) {
                    label.textContent = "利用不可";
                }
            });
        });
    }

    /* =========================================
       15. 選択日の教室予約状況を取得して反映
    ========================================= */
    function updateRoomStatuses(date) {
        const roomGroups = document.querySelectorAll(".room-group");
        if (!roomGroups.length) return;

        updateRoomCardLinks(date);

        fetch(`/user/room-status?date=${encodeURIComponent(date)}`, {
            cache: "no-store",
            headers: { Accept: "application/json" },
        })
            .then(async (response) => {
                if (!response.ok) {
                    const errorText = await response.text().catch(() => "");

                    // HTTPステータス別に原因が分かるようログ出力
                    let hint = "";
                    switch (response.status) {
                        case 401:
                        case 419:
                            hint = "セッション切れ/未ログインの可能性。ログインし直してください。";
                            break;
                        case 403:
                            hint = "権限エラー。ルートのミドルウェア設定を確認してください。";
                            break;
                        case 404:
                            hint = "ルート未定義。/user/room-status のルート定義を確認してください。";
                            break;
                        case 500:
                            hint = "サーバー側エラー。getStatusForPeriod 等の処理を確認してください。";
                            break;
                        default:
                            hint = "想定外のHTTPステータスです。";
                    }

                    console.error(
                        `[raqoo] room-status APIエラー: HTTP ${response.status} - ${hint}`
                    );
                    console.error("[raqoo] レスポンス本文:", errorText.slice(0, 500));

                    throw new Error(`room-status HTTP ${response.status}`);
                }

                // JSON以外（HTMLのエラーページ等）が返ってきた場合も検知
                const contentType = response.headers.get("content-type") || "";
                if (!contentType.includes("application/json")) {
                    const text = await response.text().catch(() => "");
                    console.error(
                        "[raqoo] room-status がJSONを返していません。content-type:",
                        contentType
                    );
                    console.error("[raqoo] レスポンス本文(先頭):", text.slice(0, 500));
                    throw new Error("room-status response is not JSON");
                }

                return response.json();
            })
            .then((data) => {
                // レスポンス形式の揺れを吸収（配列 / {statuses:[]} / {data:[]}）
                const statusList = Array.isArray(data)
                    ? data
                    : data.statuses ?? data.data ?? [];

                if (!Array.isArray(statusList)) {
                    console.error("[raqoo] room-status の形式が不正です:", data);
                    throw new Error("room-status format invalid");
                }

                console.log("[raqoo] room-status 取得成功:", statusList);

                roomGroups.forEach((roomGroup) => {
                    const roomId = roomGroup.dataset.roomId;

                    roomGroup.querySelectorAll(".time-card").forEach((card) => {
                        const period = Number(card.dataset.period);

                        // キー名の揺れ（room_id / roomId / classroom_id, period / period_id）を吸収
                        const statusData = statusList.find((item) => {
                            const itemRoomId =
                                item.room_id ?? item.roomId ?? item.classroom_id;
                            const itemPeriod =
                                item.period ?? item.period_id ?? item.periodId;
                            return (
                                String(itemRoomId) === String(roomId) &&
                                Number(itemPeriod) === period
                            );
                        });

                        // status文字列を正規化（大文字/別名/未知値を吸収）
                        const status = normalizeStatus(statusData?.status);

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
                    API失敗時の保険：
                    - Blade初期表示を残しつつ、「読み込み中...」が残っていれば文言を戻す
                    - これで「読み込み中...」のまま固まる問題を防ぐ
                */
                console.error("[raqoo] ステータス取得エラー:", error);
                restoreInitialLabels();
            });
    }

    /* =========================================
       16. 日付タブを描画
    ========================================= */
    function renderStrip() {
        if (!tabs.length) return;

        tabs.forEach((tab, index) => {
            const date = new Date(startDate.getTime() + index * 86400000);

            const dayLabel = tab.querySelector(".day-label");
            const dateLabel = tab.querySelector(".date-label");

            if (dayLabel) dayLabel.textContent = jaWeek[date.getDay()];
            if (dateLabel) dateLabel.textContent = toMMDD(date);

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
            if (!iso) return;

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
        if (Number.isNaN(date.getTime())) return;

        selectedDate = date;
        startDate = new Date(selectedDate.getTime() - selectedDate.getDay() * 86400000);
        updateCenterDisplay(selectedDate);
        renderStrip();
    });
});