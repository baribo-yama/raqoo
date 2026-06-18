document.addEventListener("DOMContentLoaded", function () {
    /*
        reservation-form.js

        役割：
        - 利用目的の文字数カウント
        - 利用メンバー入力の管理
        - メンバー入力内容を hidden にまとめて送信
        - 連続予約の選択可否制御
        - 選択中時限の仮ロック / 解除API通信

        注意：
        - このJSは予約申請フォーム画面専用
        - #selectingMeta が存在する場合のみ、仮ロックAPI通信を行う
        - サイドバー開閉処理は共通JSの home.js に統一する
    */

    // =========================================
    // 01. HTML要素の取得
    // =========================================

    const purposeTextarea = document.getElementById("purpose");
    const purposeCount = document.getElementById("purposeCount");

    const membersHidden = document.getElementById("membersHidden");
    const membersProxy = document.getElementById("members");
    const memberInputs = document.querySelectorAll(".member-name-input");

    const reservationForm = document.getElementById("reservationForm");
    const selectingMeta = document.getElementById("selectingMeta");
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');

    const checkboxes = document.querySelectorAll(".period-checkbox");
    const instructionText = document.getElementById("instructionText");

    // 予約申請フォームがないページで読み込まれても、エラーにならないようにする。
    if (!reservationForm) {
        return;
    }

    // =========================================
    // 02. 仮ロックAPI用メタ情報
    // =========================================

    const roomId = selectingMeta?.dataset.roomId || "";
    const date = selectingMeta?.dataset.date || "";
    const toggleUrl = selectingMeta?.dataset.toggleUrl || "";
    const csrf = csrfMeta?.content || "";

    const canUseSelectingApi = Boolean(roomId && date && toggleUrl && csrf);

    // =========================================
    // 03. 基準時限の取得
    // =========================================

    let mainPeriod = 0;

    for (const checkbox of checkboxes) {
        if (checkbox.dataset.original === "1") {
            mainPeriod = parseInt(checkbox.value, 10);
        }
    }

    initializeReservationForm();

    // =========================================
    // 04. 初期化
    // =========================================

    /*
        予約申請フォームで使う初期表示とイベント設定をまとめて実行する。
    */
    function initializeReservationForm() {
        setupPurposeCount();
        restoreMembersProxyAfterValidationError();
        setupMemberInputEvents();
        setupFormSubmitEvent();
        setupPeriodCheckboxEvents();

        updateFormState();
    }

    // =========================================
    // 05. 利用目的の文字数カウント
    // =========================================

    /*
        利用目的の現在文字数を表示する。
    */
    function setupPurposeCount() {
        if (!purposeTextarea || !purposeCount) {
            return;
        }

        purposeTextarea.addEventListener("input", function () {
            updatePurposeCount();
        });

        updatePurposeCount();
    }

    /*
        利用目的の文字数表示を更新する。
    */
    function updatePurposeCount() {
        if (!purposeTextarea || !purposeCount) {
            return;
        }

        purposeCount.textContent = purposeTextarea.value.length;
    }

    // =========================================
    // 06. 利用メンバー入力の管理
    // =========================================

    /*
        バリデーションエラーで戻ったとき、old()で復元されたメンバー入力を連続予約制御に反映する。
    */
    function restoreMembersProxyAfterValidationError() {
        if (!membersProxy) {
            return;
        }

        membersProxy.value = hasAnyMemberInput() ? "dummy" : "";
    }

    /*
        利用メンバー入力欄の変更を監視する。
    */
    function setupMemberInputEvents() {
        for (const memberInput of memberInputs) {
            memberInput.addEventListener("input", function () {
                updateMembersProxy();
            });
        }
    }

    /*
        1人でもメンバー入力があれば membersProxy に dummy を入れる。
        連続予約制御は membersProxy の値を見て判断する。
    */
    function updateMembersProxy() {
        if (!membersProxy) {
            return;
        }

        membersProxy.value = hasAnyMemberInput() ? "dummy" : "";
        membersProxy.dispatchEvent(new Event("input"));
    }

    /*
        利用メンバーが1人以上入力されているか判定する。
    */
    function hasAnyMemberInput() {
        return getFilledMemberNames().length > 0;
    }

    /*
        空欄を除いた利用メンバー名だけを配列で取得する。
    */
    function getFilledMemberNames() {
        return Array.from(memberInputs)
            .map((input) => input.value.trim())
            .filter((value) => value !== "");
    }

    // =========================================
    // 07. フォーム送信時のメンバー集約
    // =========================================

    /*
        フォーム送信直前に、30行のメンバー入力を membersHidden にまとめる。
    */
    function setupFormSubmitEvent() {
        if (!membersHidden) {
            return;
        }

        reservationForm.addEventListener("submit", function () {
            membersHidden.value = getFilledMemberNames().join("\n");
        });
    }

    // =========================================
    // 08. 仮ロックAPI通信
    // =========================================

    /*
        指定時限の仮ロック / 仮ロック解除を行う。

        action:
        - lock   : 選択中として仮ロックする
        - unlock : 仮ロックを解除する
    */
    async function toggleSelectingApi(period, action) {
        if (!canUseSelectingApi) {
            console.warn("selecting API に必要な情報が不足しています。");
            return false;
        }

        try {
            const response = await fetch(toggleUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrf,
                    Accept: "application/json",
                },
                body: JSON.stringify({
                    room_id: roomId,
                    date: date,
                    period: period,
                    action: action,
                }),
            });

            const data = await parseJsonResponse(response);

            if (!response.ok) {
                console.error("selecting API error:", data);
                return false;
            }

            if (!data.success) {
                return false;
            }

            return true;
        } catch (error) {
            console.error("selecting toggle error:", error);
            return false;
        }
    }

    /*
        APIレスポンスをJSONとして読み取る。
        Laravel側で500エラーHTMLが返った場合でも、原因をConsoleで追いやすくする。
    */
    async function parseJsonResponse(response) {
        const responseText = await response.text();

        if (!responseText) {
            return {};
        }

        try {
            return JSON.parse(responseText);
        } catch (error) {
            console.error(
                "JSONとして解析できないレスポンスです:",
                responseText,
            );
            throw error;
        }
    }

    // =========================================
    // 09. 連続予約の選択制御
    // =========================================

    /*
        メンバー入力状態が変わったとき、連続予約の選択可否を更新する。
    */
    if (membersProxy) {
        membersProxy.addEventListener("input", function () {
            updateFormState();
        });
    }

    /*
        フォーム全体の選択状態を更新する。

        ルール：
        - メンバー未入力の場合、追加の連続予約はできない
        - 追加で選べる時限は、基準時限の前後どちらか1つまで
        - 授業中 / 予約済みの時限は選択できない
    */
    async function updateFormState() {
        const hasMembers = Boolean(
            membersProxy && membersProxy.value.trim().length > 0,
        );

        updateInstructionText(hasMembers);

        const extraCheckedCount = countExtraCheckedPeriods();

        for (const checkbox of checkboxes) {
            const label = document.getElementById("label-" + checkbox.value);

            if (!label) {
                continue;
            }

            const isOriginal = checkbox.dataset.original === "1";
            const isReserved = checkbox.dataset.reserved === "1";
            const currentPeriod = parseInt(checkbox.value, 10);

            if (isOriginal) {
                continue;
            }

            resetPeriodLabelState(label);

            if (isReserved) {
                disablePeriodCheckbox(checkbox, label);
                continue;
            }

            if (!hasMembers) {
                await uncheckWithUnlock(checkbox, label);
                disablePeriodCheckbox(checkbox, label);
                continue;
            }

            if (!isConsecutivePeriod(currentPeriod)) {
                await uncheckWithUnlock(checkbox, label);
                disablePeriodCheckbox(checkbox, label);
                continue;
            }

            if (extraCheckedCount >= 1 && !checkbox.checked) {
                disablePeriodCheckbox(checkbox, label);
                continue;
            }

            enablePeriodCheckbox(checkbox, label);

            if (checkbox.checked) {
                setPeriodLabelSelected(label);
            }
        }
    }

    /*
        画面上の説明文を、メンバー入力状態に合わせて更新する。
    */
    function updateInstructionText(hasMembers) {
        if (!instructionText) {
            return;
        }

        if (hasMembers) {
            instructionText.textContent = "※最大2コマまで選択可能です。";
            instructionText.classList.remove("text-orange-600");
            instructionText.classList.add("text-blue-600");
            return;
        }

        instructionText.textContent =
            "※1名利用（メンバー未入力）の場合は連続予約できません。";
        instructionText.classList.add("text-orange-600");
        instructionText.classList.remove("text-blue-600");
    }

    /*
        追加でチェックされている時限数を取得する。
        元々選択していた時限は数えない。
    */
    function countExtraCheckedPeriods() {
        let extraCheckedCount = 0;

        for (const checkbox of checkboxes) {
            const isOriginal = checkbox.dataset.original === "1";

            if (!isOriginal && checkbox.checked) {
                extraCheckedCount += 1;
            }
        }

        return extraCheckedCount;
    }

    /*
        指定時限が基準時限の前後どちらかか判定する。
    */
    function isConsecutivePeriod(period) {
        return period === mainPeriod - 1 || period === mainPeriod + 1;
    }

    /*
        チェックを外し、必要なら仮ロック解除APIを実行する。
    */
    async function uncheckWithUnlock(checkbox, label) {
        if (!checkbox.checked) {
            return;
        }

        const period = parseInt(checkbox.value, 10);

        checkbox.checked = false;
        setPeriodLabelDefault(label);

        await toggleSelectingApi(period, "unlock");
    }

    // =========================================
    // 10. 時限チェックボックス変更時の処理
    // =========================================

    /*
        時限チェックボックスの変更イベントを設定する。
    */
    function setupPeriodCheckboxEvents() {
        for (const checkbox of checkboxes) {
            checkbox.addEventListener("change", async function () {
                await handlePeriodCheckboxChange(this);
            });
        }
    }

    /*
        時限チェックボックスが変更されたとき、仮ロックAPIと表示を更新する。
    */
    async function handlePeriodCheckboxChange(checkbox) {
        const label = document.getElementById("label-" + checkbox.value);
        const period = parseInt(checkbox.value, 10);
        const isOriginal = checkbox.dataset.original === "1";

        if (!label || isOriginal) {
            return;
        }

        if (checkbox.checked) {
            await lockSelectedPeriod(checkbox, label, period);
        } else {
            await unlockSelectedPeriod(label, period);
        }

        await updateFormState();
    }

    /*
        追加時限を選択したとき、仮ロックAPIを実行する。
    */
    async function lockSelectedPeriod(checkbox, label, period) {
        const success = await toggleSelectingApi(period, "lock");

        if (!success) {
            checkbox.checked = false;
            alert("その時間帯はすでに他の人が選択中、または予約できません。");
            return;
        }

        setPeriodLabelSelected(label);
    }

    /*
        追加時限の選択を外したとき、仮ロック解除APIを実行する。
    */
    async function unlockSelectedPeriod(label, period) {
        await toggleSelectingApi(period, "unlock");
        setPeriodLabelDefault(label);
    }

    // =========================================
    // 11. 時限ラベルの表示切り替え
    // =========================================

    /*
        時限ラベルから、JSで付け替える状態classだけを一度外す。
        以前の状態が残って見た目が崩れるのを防ぐ。
    */
    function resetPeriodLabelState(label) {
        label.classList.remove(
            "bg-blue-50",
            "border-blue-400",
            "opacity-50",
            "cursor-not-allowed",
        );
    }

    /*
        時限を選択可能状態にする。
    */
    function enablePeriodCheckbox(checkbox, label) {
        checkbox.disabled = false;
        label.classList.remove("opacity-50", "cursor-not-allowed");
        label.classList.add("cursor-pointer");
    }

    /*
        時限を選択不可状態にする。
    */
    function disablePeriodCheckbox(checkbox, label) {
        checkbox.disabled = true;
        label.classList.add("opacity-50", "cursor-not-allowed");
        label.classList.remove("cursor-pointer");
    }

    /*
        時限ラベルを選択中の見た目にする。
    */
    function setPeriodLabelSelected(label) {
        label.classList.add("bg-blue-50", "border-blue-400");
        label.classList.remove("border-gray-200");
    }

    /*
        時限ラベルを通常の見た目に戻す。
    */
    function setPeriodLabelDefault(label) {
        label.classList.remove("bg-blue-50", "border-blue-400");
        label.classList.add("border-gray-200");
    }
});
