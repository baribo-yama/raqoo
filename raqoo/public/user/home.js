document.addEventListener("DOMContentLoaded", () => {
    // ==================================================
    // 共通設定・定数
    // ==================================================

    // Date.getDay() の数字（0〜6）を、画面表示用の曜日に変換する表。
    const WEEK_DAYS_JA = ["日", "月", "火", "水", "木", "金", "土"];

    // 1日分のミリ秒。前日・翌日・週タブの日付計算で使う。
    const ONE_DAY_MS = 86400000;

    // この幅以下のときだけ、スマホ用の予約状況シートを使う。
    const MOBILE_SHEET_MEDIA = "(max-width: 560px)";

    // サイドバーをスマホ表示として扱う画面幅。
    const MOBILE_SIDEBAR_MEDIA = "(max-width: 1024px)";

    // 時限ごとの開始時刻・終了時刻。
    // スマホ用予約状況シートのカードで「1限 09:10-10:40」のように表示するために使う。
    const PERIOD_TIME_TABLE = {
        1: ["09:10", "10:40"],
        2: ["10:50", "12:20"],
        3: ["13:00", "14:30"],
        4: ["14:40", "16:10"],
        5: ["16:20", "17:50"],
        6: ["18:00", "19:30"],
    };

    // スマホ用予約状況シートで使う、予約ステータスごとの表示設定。
    // status の英語名を、日本語文言とCSSクラスに変換するための表。
    const RESERVATION_BADGE_META = {
        pending: { text: "申請中", className: "my-badge pending" },
        reserved: { text: "予約済み", className: "my-badge reserved" },
        in_use: { text: "利用中", className: "my-badge in_use" },
        finished: { text: "利用完了", className: "my-badge completed" },
        cancelled: { text: "キャンセル", className: "my-badge cancelled" },
        rejected: { text: "却下", className: "my-badge rejected" },
        approved: { text: "予約済み", className: "my-badge reserved" },
        completed: { text: "完了", className: "my-badge completed" },
    };

    // 教室カードが予約不可のとき、statusから表示文言を決めるための表。
    // 授業名 lesson_title を優先する処理は、この表ではなく getUnavailableText() で行う。
    const UNAVAILABLE_STATUS_TEXT = {
        pending: "申請中",
        reserved: "予約済み",
        in_use: "利用中",
        unavailable: "利用不可",
        conflict: "同時刻に予約あり",
        selecting: "選択中",
    };

    // 教室カードから削除したいステータスclassの一覧。
    // 実際の削除は removeRoomStatusClasses() で行う。
    const ROOM_STATUS_CLASSES = [
        "available",
        "pending",
        "reserved",
        "in_use",
        "unavailable",
        "conflict",
        "selecting",
        "locked",
    ];

    // ==================================================
    // 画面で使うHTML要素
    // ==================================================

    // この画面で使うHTML要素を取得して、後から使えるようにまとめておく。
    // ここでは取得だけを行い、表示更新やAPI送信はしない。
    const elements = {
        // スマホ用サイドバーまわり
        // 実際のホーム画面では .nav-toggle が存在していたため、既存HTMLに合わせて取得する。
        // .m-hamburger も残しておくことで、他画面で同名classを使っていても対応できる。
        hamburgerButtons: document.querySelectorAll(
            ".nav-toggle, .m-hamburger",
        ),
        navBackdrop: document.querySelector(".m-backdrop"),

        // 日付表示まわり
        mainDate: document.querySelector(".date-main"),
        weekDay: document.querySelector(".date-weekday"),
        dateInput: document.querySelector("#dateInput"),

        // 週タブまわり
        dayTabs: document.querySelectorAll(".day-tabs .day-tab"),
        previousButton: document.querySelector(".strip-prev"),
        nextButton: document.querySelector(".strip-next"),

        // スマホ用予約状況シートまわり
        openSheetButton: document.querySelector("#openStatusSheet"),
        closeSheetButton: document.querySelector("#closeStatusSheet"),
        statusSheet: document.querySelector("#statusSheet"),
        statusSheetBody: document.querySelector("#statusSheetBody"),
    };

    // 現在選択されている日付。inputに値があればその日付を使い、なければ今日を使う。
    let selectedDate = getInitialSelectedDate();

    // 週タブの先頭日。週タブは日曜日から7日分を表示する。
    let weekStartDate = getWeekStartDate(selectedDate);

    initializePage();

    // ==================================================
    // 初期表示・イベント設定
    // ==================================================

    /**
     * ページ初期表示とイベント設定をまとめて実行する。
     */
    function initializePage() {
        setupMobileSidebar();

        if (!isHomeReservationPage()) {
            return;
        }

        setupMobileStatusSheetEvents();
        setupDayTabEvents();
        setupDateMoveEvents();
        setupDateInputEvent();

        updateSelectedDateView(selectedDate);
        renderWeekTabs();
    }

    /**
     * ホーム画面の予約表示処理を実行してよいページか判定する。
     * home.js は共通で読み込まれることがあるため、必要な要素がないページでは予約APIを呼ばない。
     */
    function isHomeReservationPage() {
        return Boolean(elements.dateInput && elements.mainDate);
    }

    // ==================================================
    // 共通: スマホ用サイドバー
    // ==================================================

    /**
     * 既存のハンバーガーボタンで、スマホ用サイドバーを開閉する。
     * CSS側は body.nav-open を見てサイドバー表示を切り替える。
     */
    function setupMobileSidebar() {
        if (elements.hamburgerButtons.length === 0) {
            console.warn("サイドバー用のハンバーガーボタンが見つかりません。");
        }

        for (const hamburgerButton of elements.hamburgerButtons) {
            hamburgerButton.setAttribute("aria-expanded", "false");

            hamburgerButton.addEventListener("click", (event) => {
                event.preventDefault();
                event.stopPropagation();

                toggleMobileSidebar();
            });
        }

        if (elements.navBackdrop) {
            elements.navBackdrop.addEventListener("click", () => {
                closeMobileSidebar();
            });
        }

        document.addEventListener("keydown", (event) => {
            if (event.key !== "Escape") {
                return;
            }

            closeMobileSidebar();
        });

        window.addEventListener("resize", () => {
            if (isMobileSidebarSize()) {
                return;
            }

            closeMobileSidebar();
        });
    }

    /**
     * スマホ用サイドバーを使う画面幅か判定する。
     */
    function isMobileSidebarSize() {
        return window.matchMedia(MOBILE_SIDEBAR_MEDIA).matches;
    }

    /**
     * サイドバーを開いている場合は閉じ、閉じている場合は開く。
     */
    function toggleMobileSidebar() {
        if (document.body.classList.contains("nav-open")) {
            closeMobileSidebar();
            return;
        }

        openMobileSidebar();
    }

    /**
     * スマホ用サイドバーを開く。
     */
    function openMobileSidebar() {
        document.body.classList.add("nav-open");
        updateHamburgerExpandedState(true);
    }

    /**
     * スマホ用サイドバーを閉じる。
     */
    function closeMobileSidebar() {
        document.body.classList.remove("nav-open");
        updateHamburgerExpandedState(false);
    }

    /**
     * ハンバーガーボタンの開閉状態を支援技術にも伝える。
     */
    function updateHamburgerExpandedState(isExpanded) {
        for (const hamburgerButton of elements.hamburgerButtons) {
            hamburgerButton.setAttribute(
                "aria-expanded",
                isExpanded ? "true" : "false",
            );
        }
    }

    // ==================================================
    // 共通: 日付の変換・計算
    // ==================================================

    /**
     * 月や日などの日付用の数字を2桁の文字列に変換する。
     * YYYY-MM-DD や MM/DD の形に整えるために使う。
     */
    function formatDateNumberToTwoDigits(number) {
        return String(number).padStart(2, "0");
    }

    /**
     * DateをAPI送信用の YYYY-MM-DD 形式に変換する。
     */
    function formatDateForApi(date) {
        const yearText = date.getFullYear();
        const monthText = formatDateNumberToTwoDigits(date.getMonth() + 1);
        const dayText = formatDateNumberToTwoDigits(date.getDate());

        return `${yearText}-${monthText}-${dayText}`;
    }

    /**
     * Dateを画面表示用の MM/DD 形式に変換する。
     */
    function formatDateForView(date) {
        const monthText = formatDateNumberToTwoDigits(date.getMonth() + 1);
        const dayText = formatDateNumberToTwoDigits(date.getDate());

        return `${monthText}/${dayText}`;
    }

    /**
     * YYYY-MM-DD 形式の文字列を Date に変換する。
     * 変換できない場合は null を返す。
     */
    function parseDateText(dateText) {
        const dateParts = dateText.split("-");

        if (dateParts.length !== 3) {
            return null;
        }

        const year = Number(dateParts[0]);
        const month = Number(dateParts[1]);
        const day = Number(dateParts[2]);

        if (!year || !month || !day) {
            return null;
        }

        // JavaScriptのDateは月が0始まりなので、month - 1 にする。
        const parsedDate = new Date(year, month - 1, day);

        if (Number.isNaN(parsedDate.getTime())) {
            return null;
        }

        return parsedDate;
    }

    /**
     * 初期表示で選択する日付を取得する。
     */
    function getInitialSelectedDate() {
        if (!elements.dateInput || !elements.dateInput.value) {
            return new Date();
        }

        const inputDate = parseDateText(elements.dateInput.value);

        if (!inputDate) {
            return new Date();
        }

        return inputDate;
    }

    /**
     * 指定日の週の先頭日（日曜日）を取得する。
     */
    function getWeekStartDate(date) {
        const daysFromSunday = date.getDay();
        const weekStartTime = date.getTime() - daysFromSunday * ONE_DAY_MS;

        return new Date(weekStartTime);
    }

    /**
     * 2つの日付が同じ年月日か判定する。
     */
    function isSameDate(firstDate, secondDate) {
        const isSameYear = firstDate.getFullYear() === secondDate.getFullYear();
        const isSameMonth = firstDate.getMonth() === secondDate.getMonth();
        const isSameDay = firstDate.getDate() === secondDate.getDate();

        return isSameYear && isSameMonth && isSameDay;
    }

    /**
     * 現在選択されている日付を YYYY-MM-DD 形式で取得する。
     */
    function getSelectedDateText() {
        if (elements.dateInput && elements.dateInput.value) {
            return elements.dateInput.value;
        }

        return formatDateForApi(new Date());
    }

    // ==================================================
    // 共通: API取得処理
    // ==================================================

    /**
     * 指定日の自分の予約一覧をAPIから取得する。
     * バックエンドAPIに関わる取得処理なので、関数名とAPI URLは変更しない。
     */
    async function fetchMyReservations(dateText) {
        const requestUrl = `/user/my-reservations?date=${encodeURIComponent(dateText)}`;

        const response = await fetch(requestUrl, {
            cache: "no-store",
        });

        return response.json();
    }

    /**
     * 指定日の教室ステータスをAPIから取得する。
     * バックエンドAPIに関わる取得処理なので、関数名とAPI URLは変更しない。
     */
    async function fetchRoomStatuses(dateText) {
        const response = await fetch(`/user/room-status?date=${dateText}`, {
            cache: "no-store",
        });

        return response.json();
    }

    // ==================================================
    // 共通: 日付表示・週タブ・日付移動
    // ==================================================

    /**
     * 選択日が変わったとき、日付表示・教室カード・スマホ用予約状況シートを更新する。
     */
    function updateSelectedDateView(date) {
        // API送信や予約リンクの日付更新で使うため、YYYY-MM-DD 形式に変換する。
        const dateText = formatDateForApi(date);

        if (elements.mainDate) {
            elements.mainDate.textContent = formatDateForView(date);
        }

        if (elements.weekDay) {
            elements.weekDay.textContent = `（${WEEK_DAYS_JA[date.getDay()]}）`;
        }

        // 現在選択中の日付を、予約状況シートや予約リンク更新でも使えるように保持する。
        if (elements.dateInput) {
            elements.dateInput.value = dateText;
        }

        reloadRoomStatuses(dateText);
        refreshMobileStatusSheetIfOpen(dateText);
    }

    /**
     * 週表示ストリップの日付と active 状態を描画する。
     */
    function renderWeekTabs() {
        for (let index = 0; index < elements.dayTabs.length; index += 1) {
            const dayTab = elements.dayTabs[index];

            // 週の先頭日から index 日分進めて、このタブの日付を作る。
            const tabDate = new Date(
                weekStartDate.getTime() + index * ONE_DAY_MS,
            );

            updateWeekTab(dayTab, tabDate);
        }
    }

    /**
     * 日付タブ1つ分の曜日・日付・active状態を更新する。
     */
    function updateWeekTab(dayTab, tabDate) {
        const dayLabel = dayTab.querySelector(".day-label");
        const dateLabel = dayTab.querySelector(".date-label");

        if (dayLabel) {
            dayLabel.textContent = WEEK_DAYS_JA[tabDate.getDay()];
        }

        if (dateLabel) {
            dateLabel.textContent = formatDateForView(tabDate);
        }

        // クリック時にこの日付を選択できるよう、YYYY-MM-DD形式の日付を保存する。
        dayTab.dataset.date = formatDateForApi(tabDate);

        // このタブの日付が選択中の日付と同じ場合だけ active を付ける。
        dayTab.classList.toggle("active", isSameDate(tabDate, selectedDate));
    }

    /**
     * 日付タブ押下時のイベントを設定する。
     */
    function setupDayTabEvents() {
        for (const dayTab of elements.dayTabs) {
            dayTab.addEventListener("click", () => {
                selectDateFromTab(dayTab);
            });
        }
    }

    /**
     * 押下された日付タブの日付を、現在の選択日に反映する。
     */
    function selectDateFromTab(dayTab) {
        const dateText = dayTab.dataset.date;

        if (!dateText) {
            return;
        }

        const nextSelectedDate = parseDateText(dateText);

        if (!nextSelectedDate) {
            return;
        }

        selectedDate = nextSelectedDate;
        weekStartDate = getWeekStartDate(selectedDate);

        updateSelectedDateView(selectedDate);
        renderWeekTabs();
    }

    /**
     * 前日・翌日ボタンのイベントを設定する。
     */
    function setupDateMoveEvents() {
        if (elements.previousButton) {
            elements.previousButton.addEventListener(
                "click",
                moveToPreviousDay,
            );
        }

        if (elements.nextButton) {
            elements.nextButton.addEventListener("click", moveToNextDay);
        }
    }

    /**
     * 選択日を1日前に移動する。
     */
    function moveToPreviousDay() {
        selectedDate = new Date(selectedDate.getTime() - ONE_DAY_MS);
        weekStartDate = new Date(weekStartDate.getTime() - ONE_DAY_MS);

        updateSelectedDateView(selectedDate);
        renderWeekTabs();
    }

    /**
     * 選択日を1日後に移動する。
     */
    function moveToNextDay() {
        selectedDate = new Date(selectedDate.getTime() + ONE_DAY_MS);
        weekStartDate = new Date(weekStartDate.getTime() + ONE_DAY_MS);

        updateSelectedDateView(selectedDate);
        renderWeekTabs();
    }

    /**
     * 日付入力欄が変更されたときのイベントを設定する。
     */
    function setupDateInputEvent() {
        if (!elements.dateInput) {
            return;
        }

        elements.dateInput.addEventListener("change", selectDateFromInput);
    }

    /**
     * 日付入力欄の値を、現在の選択日に反映する。
     */
    function selectDateFromInput() {
        const nextSelectedDate = parseDateText(elements.dateInput.value);

        if (!nextSelectedDate) {
            return;
        }

        selectedDate = nextSelectedDate;
        weekStartDate = getWeekStartDate(selectedDate);

        updateSelectedDateView(selectedDate);
        renderWeekTabs();
    }

    // ==================================================
    // PC・スマホ共通: 教室カードの空き状況更新
    // ==================================================

    /**
     * 指定日の教室ステータスを取得して、教室カードへ反映する。
     */
    async function reloadRoomStatuses(dateText) {
        showRoomCardsLoading(dateText);

        try {
            const roomStatuses = await fetchRoomStatuses(dateText);
            applyRoomStatusesToCards(roomStatuses);
        } catch (error) {
            console.error("ステータス取得エラー:", error);
        }
    }

    /**
     * 全ての教室カードを、一時的に読み込み中表示にする。
     */
    function showRoomCardsLoading(dateText) {
        const roomGroups = document.querySelectorAll(".room-group");

        for (const roomGroup of roomGroups) {
            const timeCards = roomGroup.querySelectorAll(".time-card");

            for (const timeCard of timeCards) {
                resetRoomCardToLoading(timeCard, dateText);
            }
        }
    }

    /**
     * 教室カード1枚を、API取得前の読み込み中状態に戻す。
     */
    function resetRoomCardToLoading(timeCard, dateText) {
        // 前回の日付で付いた available / reserved / unavailable などのclassを削除する。
        removeRoomStatusClasses(timeCard);

        const availableLabel = timeCard.querySelector(".available-label");
        const unavailableLabel = timeCard.querySelector(".unavailable-label");
        const reserveButton = timeCard.querySelector(".reserve-btn");

        // API取得中は予約可能か判断できないため、予約ボタンは一時的に隠す。
        hideElement(availableLabel);
        showElement(unavailableLabel);
        hideElement(reserveButton);

        if (unavailableLabel) {
            unavailableLabel.textContent = "読み込み中...";
        }

        // API取得後に予約可能になった場合、この日付のリンクで予約画面へ進めるようにする。
        updateReservationLinkDate(timeCard, reserveButton, dateText);
    }

    /**
     * APIから取得した教室ステータスを、画面上の全ての教室カードに反映する。
     */
    function applyRoomStatusesToCards(roomStatuses) {
        if (!Array.isArray(roomStatuses)) {
            return;
        }

        const roomGroups = document.querySelectorAll(".room-group");

        for (const roomGroup of roomGroups) {
            const roomId = roomGroup.dataset.roomId;
            const timeCards = roomGroup.querySelectorAll(".time-card");

            for (const timeCard of timeCards) {
                updateRoomCardStatus(timeCard, roomId, roomStatuses);
            }
        }
    }

    /**
     * 教室カード1枚に、該当するステータスを反映する。
     */
    function updateRoomCardStatus(timeCard, roomId, roomStatuses) {
        const period = Number(timeCard.dataset.period);

        // API結果の中から、この教室ID・この時限に一致するステータスデータを探す。
        const statusData = findRoomStatus(roomStatuses, roomId, period);

        // 一致するデータがなければ予約可能として扱う。
        const status = getRoomStatusName(statusData);

        removeRoomStatusClasses(timeCard);
        timeCard.classList.add(status);

        if (status === "available") {
            setRoomCardAvailable(timeCard);
            return;
        }

        setRoomCardUnavailable(timeCard, status, statusData);
    }

    /**
     * 教室IDと時限が一致するステータスデータを探す。
     */
    function findRoomStatus(roomStatuses, roomId, period) {
        return roomStatuses.find((roomStatus) => {
            const isSameRoom = String(roomStatus.room_id) === String(roomId);
            const isSamePeriod = Number(roomStatus.period) === period;

            return isSameRoom && isSamePeriod;
        });
    }

    /**
     * ステータスデータから status 名を取得する。
     */
    function getRoomStatusName(statusData) {
        if (!statusData) {
            return "available";
        }

        return statusData.status;
    }

    /**
     * 教室カードを予約可能状態として表示する。
     * PC表示用とスマホ表示用の両方を切り替える。
     */
    function setRoomCardAvailable(timeCard) {
        const pcParts = getPcRoomCardParts(timeCard);
        const spParts = getSpRoomCardParts(timeCard);

        // 予約可能なカードはクリックできるように、保存しておいた予約リンクを戻す。
        if (timeCard.dataset.baseHref) {
            timeCard.setAttribute("href", timeCard.dataset.baseHref);
        }

        timeCard.classList.remove("locked");
        timeCard.removeAttribute("aria-disabled");

        showElement(pcParts.availableLabel);
        hideElement(pcParts.unavailableLabel);
        showElement(pcParts.reserveButton);

        showElement(spParts.availableLabel);
        hideElement(spParts.unavailableLabel);
        showElement(spParts.reserveButton);
    }

    /**
     * 教室カードを予約不可状態として表示する。
     * PC表示用とスマホ表示用の両方を切り替える。
     */
    function setRoomCardUnavailable(timeCard, status, statusData) {
        const pcParts = getPcRoomCardParts(timeCard);
        const spParts = getSpRoomCardParts(timeCard);
        const unavailableText = getUnavailableText(status, statusData);

        // 予約不可のカードはクリックできないようにする。
        timeCard.removeAttribute("href");
        timeCard.classList.add("locked");
        timeCard.setAttribute("aria-disabled", "true");

        hideElement(pcParts.availableLabel);
        showElement(pcParts.unavailableLabel);
        hideElement(pcParts.reserveButton);
        setTextContent(pcParts.unavailableLabel, unavailableText);

        hideElement(spParts.availableLabel);
        showElement(spParts.unavailableLabel);
        hideElement(spParts.reserveButton);
        setTextContent(spParts.unavailableLabel, unavailableText);
    }

    /**
     * PC表示用の教室カード要素を取得する。
     */
    function getPcRoomCardParts(timeCard) {
        return {
            availableLabel: timeCard.querySelector(
                ".pc-layout .available-label",
            ),
            unavailableLabel: timeCard.querySelector(
                ".pc-layout .unavailable-label",
            ),
            reserveButton: timeCard.querySelector(".reserve-btn.pc-only"),
        };
    }

    /**
     * スマホ表示用の教室カード要素を取得する。
     */
    function getSpRoomCardParts(timeCard) {
        return {
            availableLabel: timeCard.querySelector(
                ".sp-layout .available-label",
            ),
            unavailableLabel: timeCard.querySelector(
                ".sp-layout .unavailable-label",
            ),
            reserveButton: timeCard.querySelector(".sp-reserve-btn"),
        };
    }

    /**
     * 予約不可時に表示する文言を取得する。
     */
    function getUnavailableText(status, statusData) {
        if (statusData && statusData.lesson_title) {
            return statusData.lesson_title;
        }

        if (UNAVAILABLE_STATUS_TEXT[status]) {
            return UNAVAILABLE_STATUS_TEXT[status];
        }

        return "利用不可";
    }

    /**
     * 教室カードからステータス関連のCSSクラスを削除する。
     */
    function removeRoomStatusClasses(timeCard) {
        for (const statusClass of ROOM_STATUS_CLASSES) {
            timeCard.classList.remove(statusClass);
        }
    }

    // ==================================================
    // スマホ画面: 予約状況シート
    // ==================================================

    /**
     * スマホ用の予約状況シートを使う画面幅か判定する。
     */
    function isMobileSheetSize() {
        return window.matchMedia(MOBILE_SHEET_MEDIA).matches;
    }

    /**
     * スマホ用の予約状況シートを開く。
     */
    function openMobileStatusSheet() {
        document.body.classList.add("sheet-open");

        if (elements.statusSheet) {
            elements.statusSheet.setAttribute("aria-hidden", "false");
        }
    }

    /**
     * スマホ用の予約状況シートを閉じる。
     */
    function closeMobileStatusSheet() {
        document.body.classList.remove("sheet-open");

        if (elements.statusSheet) {
            elements.statusSheet.setAttribute("aria-hidden", "true");
        }
    }

    /**
     * スマホ幅のときだけ、予約状況シートの開閉イベントを設定する。
     */
    function setupMobileStatusSheetEvents() {
        if (!isMobileSheetSize()) {
            return;
        }

        if (elements.openSheetButton) {
            elements.openSheetButton.addEventListener(
                "click",
                openMobileStatusSheetForSelectedDate,
            );
        }

        if (elements.closeSheetButton) {
            elements.closeSheetButton.addEventListener(
                "click",
                closeMobileStatusSheet,
            );
        }

        autoOpenMobileStatusSheetIfDateQueryExists();
    }

    /**
     * 現在選択中の日付で予約状況を読み込み、スマホ用シートを開く。
     */
    function openMobileStatusSheetForSelectedDate() {
        const selectedDateText = getSelectedDateText();

        renderMobileReservationsSheet(selectedDateText);
        openMobileStatusSheet();
    }

    /**
     * URLに date= が含まれている場合、スマホ用予約状況シートを自動で開く。
     */
    function autoOpenMobileStatusSheetIfDateQueryExists() {
        if (!location.search.includes("date=")) {
            return;
        }

        // 画面の初期描画が終わる前に開くと表示が崩れる可能性があるため、少し遅らせる。
        setTimeout(() => {
            openMobileStatusSheetForSelectedDate();
        }, 250);
    }

    /**
     * スマホ用予約状況シートが開いている場合だけ、選択日の予約一覧を再読み込みする。
     */
    function refreshMobileStatusSheetIfOpen(dateText) {
        const isSheetOpen = document.body.classList.contains("sheet-open");

        if (!isSheetOpen) {
            return;
        }

        if (!isMobileSheetSize()) {
            return;
        }

        renderMobileReservationsSheet(dateText);
    }

    /**
     * 指定日の自分の予約一覧を取得して、スマホ用予約状況シートに表示する。
     */
    async function renderMobileReservationsSheet(dateText) {
        if (!elements.statusSheetBody) {
            return;
        }

        elements.statusSheetBody.textContent = "読み込み中...";

        try {
            const reservations = await fetchMyReservations(dateText);
            const sortedReservations =
                sortReservationsForMobileSheet(reservations);

            elements.statusSheetBody.innerHTML = "";

            if (sortedReservations.length === 0) {
                showMobileSheetMessage("この日の予約はありません");
                return;
            }

            appendMobileReservationCards(sortedReservations);
        } catch (error) {
            console.error(error);

            elements.statusSheetBody.innerHTML = "";
            showMobileSheetMessage("取得に失敗しました");
        }
    }

    /**
     * スマホ用予約状況シートに表示する予約一覧を並び替える。
     */
    function sortReservationsForMobileSheet(reservations) {
        if (!Array.isArray(reservations)) {
            return [];
        }

        return reservations.sort((currentReservation, nextReservation) => {
            const currentStatusPriority = getReservationStatusPriority(
                currentReservation.status,
            );
            const nextStatusPriority = getReservationStatusPriority(
                nextReservation.status,
            );

            if (currentStatusPriority !== nextStatusPriority) {
                return currentStatusPriority - nextStatusPriority;
            }

            if (currentReservation.date === nextReservation.date) {
                const currentPeriod = currentReservation.period || 0;
                const nextPeriod = nextReservation.period || 0;

                return currentPeriod - nextPeriod;
            }

            return String(currentReservation.date).localeCompare(
                String(nextReservation.date),
            );
        });
    }

    /**
     * 予約状況シートで上に表示したいステータス順を数値で返す。
     */
    function getReservationStatusPriority(status) {
        if (status === "reserved" || status === "approved") {
            return 0;
        }

        if (status === "pending") {
            return 1;
        }

        return 9;
    }

    /**
     * 予約がない場合や取得失敗時のメッセージを、スマホ用予約状況シートに表示する。
     */
    function showMobileSheetMessage(message) {
        if (!elements.statusSheetBody) {
            return;
        }

        const messageElement = document.createElement("div");
        messageElement.className = "my-empty";
        messageElement.textContent = message;

        elements.statusSheetBody.appendChild(messageElement);
    }

    /**
     * 予約一覧をカード形式でスマホ用予約状況シートに追加する。
     */
    function appendMobileReservationCards(reservations) {
        for (const reservation of reservations) {
            const reservationCard = createMobileReservationCard(reservation);
            elements.statusSheetBody.appendChild(reservationCard);
        }
    }

    // ==================================================
    // スマホ画面: 予約状況シート内の予約カード作成
    // ==================================================

    /**
     * スマホ用予約状況シートに表示する、予約1件分のカードを作成する。
     */
    function createMobileReservationCard(reservation) {
        const badgeMeta = getReservationBadgeMeta(reservation.status);
        const periodTimes = getPeriodTimes(reservation.period);
        const isApprovedReservation = isApprovedStatus(reservation.status);

        const reservationCard = document.createElement("div");

        // 確定済みの予約だけ、カード全体を強調表示する。
        if (isApprovedReservation) {
            reservationCard.className = "my-card is-approved";
        } else {
            reservationCard.className = "my-card";
        }

        const cardHeader = createMobileReservationCardHeader(
            reservation,
            badgeMeta,
        );
        const cardBody = createMobileReservationCardBody(
            reservation,
            periodTimes,
        );
        const cardFooter = createMobileReservationCardFooter(reservation);

        reservationCard.appendChild(cardHeader);
        reservationCard.appendChild(cardBody);
        reservationCard.appendChild(cardFooter);

        return reservationCard;
    }

    /**
     * スマホ用予約カード上部に表示する、教室名とステータスバッジを作成する。
     */
    function createMobileReservationCardHeader(reservation, badgeMeta) {
        const headerArea = document.createElement("div");
        headerArea.className = "my-top";

        const roomNameElement = document.createElement("div");
        roomNameElement.className = "my-room";

        // 教室名がない場合でも表示が空にならないようにする。
        roomNameElement.textContent = reservation.room_name || "教室";

        const statusBadgeElement = document.createElement("span");
        statusBadgeElement.className = badgeMeta.className;
        statusBadgeElement.textContent = badgeMeta.text;

        headerArea.appendChild(roomNameElement);
        headerArea.appendChild(statusBadgeElement);

        return headerArea;
    }

    /**
     * スマホ用予約カード中央に表示する、日付・時限情報を作成する。
     */
    function createMobileReservationCardBody(reservation, periodTimes) {
        const bodyArea = document.createElement("div");
        bodyArea.className = "my-mid";

        const dateItem = createReservationInfoItem("日付", reservation.date);
        const periodItem = createReservationPeriodItem(
            reservation.period,
            periodTimes,
        );

        bodyArea.appendChild(dateItem);
        bodyArea.appendChild(periodItem);

        return bodyArea;
    }

    /**
     * スマホ用予約カード下部に表示する、詳細リンクエリアを作成する。
     */
    function createMobileReservationCardFooter(reservation) {
        const footerArea = document.createElement("div");
        footerArea.className = "my-actions";

        const detailLink = document.createElement("a");
        detailLink.className = "my-detail";
        detailLink.textContent = "詳細を見る";
        detailLink.href = reservation.detail_url;

        footerArea.appendChild(detailLink);

        return footerArea;
    }

    /**
     * 予約カード内の「項目名 + 値」の1行表示を作成する。
     */
    function createReservationInfoItem(labelText, valueText) {
        const itemElement = document.createElement("div");
        itemElement.className = "my-item";

        const labelElement = document.createElement("div");
        labelElement.className = "my-k";
        labelElement.textContent = labelText;

        const valueElement = document.createElement("div");
        valueElement.className = "my-v";
        valueElement.textContent = valueText;

        itemElement.appendChild(labelElement);
        itemElement.appendChild(valueElement);

        return itemElement;
    }

    /**
     * 予約カード内の時限と時間帯の表示を作成する。
     */
    function createReservationPeriodItem(period, periodTimes) {
        const itemElement = document.createElement("div");
        itemElement.className = "my-item";

        const labelElement = document.createElement("div");
        labelElement.className = "my-k";
        labelElement.textContent = "時限";

        const valueElement = document.createElement("div");
        valueElement.className = "my-v";

        // 時限と時間帯で見た目を分けるため、時間帯だけ span にする。
        valueElement.appendChild(document.createTextNode(`${period}限 `));

        const timeElement = document.createElement("span");
        timeElement.className = "my-time";
        timeElement.textContent = `${periodTimes[0]}-${periodTimes[1]}`;

        valueElement.appendChild(timeElement);
        itemElement.appendChild(labelElement);
        itemElement.appendChild(valueElement);

        return itemElement;
    }

    /**
     * 時限から開始時刻・終了時刻を取得する。
     */
    function getPeriodTimes(period) {
        if (PERIOD_TIME_TABLE[period]) {
            return PERIOD_TIME_TABLE[period];
        }

        return ["--:--", "--:--"];
    }

    /**
     * 予約が確定扱いのステータスか判定する。
     */
    function isApprovedStatus(status) {
        return status === "reserved" || status === "approved";
    }

    /**
     * 予約ステータスを、画面表示用の日本語文言とバッジCSSクラスに変換する。
     */
    function getReservationBadgeMeta(status) {
        if (RESERVATION_BADGE_META[status]) {
            return RESERVATION_BADGE_META[status];
        }

        return {
            text: status || "不明",
            className: "my-badge other",
        };
    }

    // ==================================================
    // 共通: 予約リンクの日付更新
    // ==================================================

    /**
     * 予約リンク内の日付を、現在選択中の日付に書き換える。
     */
    function updateReservationLinkDate(timeCard, reserveButton, dateText) {
        if (!reserveButton) {
            return;
        }

        let reservationHref = getReservationHref(timeCard, reserveButton);

        if (!reservationHref) {
            return;
        }

        reservationHref = replaceDateInHref(reservationHref, dateText);

        if (reserveButton.hasAttribute("href")) {
            reserveButton.setAttribute("href", reservationHref);
        }

        if (reserveButton.hasAttribute("data-href")) {
            reserveButton.setAttribute("data-href", reservationHref);
        }

        // 予約不可から予約可能に戻すとき、最新の日付URLを復元できるように保持する。
        timeCard.dataset.baseHref = reservationHref;

        if (timeCard.hasAttribute("href")) {
            timeCard.setAttribute("href", reservationHref);
        }
    }

    /**
     * 予約ボタンや教室カードが持っている予約URLを取得する。
     */
    function getReservationHref(timeCard, reserveButton) {
        const hrefValue = reserveButton.getAttribute("href");

        if (hrefValue) {
            return hrefValue;
        }

        const dataHrefValue = reserveButton.getAttribute("data-href");

        if (dataHrefValue) {
            return dataHrefValue;
        }

        return timeCard.dataset.baseHref;
    }

    /**
     * 予約URL内の date パラメータを、指定された日付に置き換える。
     */
    function replaceDateInHref(href, dateText) {
        try {
            return replaceDateByUrlParams(href, dateText);
        } catch (error) {
            console.warn(
                "URLパースエラー。フォールバックとして正規表現を使用します",
                error,
            );
            return replaceDateByRegExp(href, dateText);
        }
    }

    /**
     * URLSearchParamsを使って、予約URL内の date パラメータを書き換える。
     */
    function replaceDateByUrlParams(href, dateText) {
        const url = new URL(href, window.location.origin);

        // 注意事項画面を経由するURLは、next の中に予約URLが入っている。
        if (url.searchParams.has("next")) {
            replaceDateInNextParam(url, dateText);
        } else if (url.searchParams.has("date")) {
            url.searchParams.set("date", dateText);
        }

        if (href.startsWith("http")) {
            return url.href;
        }

        return url.pathname + url.search;
    }

    /**
     * precautions画面を経由するURLの next パラメータ内にある date を書き換える。
     */
    function replaceDateInNextParam(url, dateText) {
        const nextParam = url.searchParams.get("next");

        if (!nextParam) {
            return;
        }

        const nextUrl = new URL(nextParam, window.location.origin);

        if (!nextUrl.searchParams.has("date")) {
            return;
        }

        nextUrl.searchParams.set("date", dateText);
        url.searchParams.set("next", nextUrl.pathname + nextUrl.search);
    }

    /**
     * URLとして解析できない場合に、正規表現で date 部分を書き換える。
     */
    function replaceDateByRegExp(href, dateText) {
        let updatedHref = href;

        // 通常の date=YYYY-MM-DD と、URLエンコードされた date%3DYYYY-MM-DD の両方に対応する。
        updatedHref = updatedHref.replace(
            /date%3D\d{4}-\d{2}-\d{2}/g,
            `date%3D${dateText}`,
        );
        updatedHref = updatedHref.replace(
            /date=\d{4}-\d{2}-\d{2}/g,
            `date=${dateText}`,
        );

        return updatedHref;
    }

    // ==================================================
    // 共通: DOM操作
    // ==================================================

    /**
     * 要素を表示状態にする。
     */
    function showElement(element) {
        if (!element) {
            return;
        }

        element.classList.remove("hidden");
    }

    /**
     * 要素を非表示状態にする。
     */
    function hideElement(element) {
        if (!element) {
            return;
        }

        element.classList.add("hidden");
    }

    /**
     * 要素が存在する場合だけテキストを設定する。
     */
    function setTextContent(element, text) {
        if (!element) {
            return;
        }

        element.textContent = text;
    }
});
