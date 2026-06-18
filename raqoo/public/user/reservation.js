document.addEventListener("DOMContentLoaded", function () {
    /*
        reservation.js

        役割：
        - 教室管理申請書画面で「＋ メンバーを追加」ボタンが押されたときに入力欄を追加する
        - 追加した入力欄にも、既存の使用メンバー入力欄と同じ name / class を付ける
        - 追加した入力欄に aria-label を付け、何人目の入力欄か分かるようにする
        - 使用メンバーの入力欄が増えすぎないように最大人数を制御する

        注意：
        - このJSは、教室管理申請書画面専用
        - サイドバー開閉処理は扱わない
        - 実際の予約申請フォーム画面の処理は reservation-form.js 側で扱う
    */

    // =========================================
    // 01. HTML要素の取得
    // =========================================

    const memberContainer = document.getElementById("member-container");
    const addMemberButton = document.getElementById("addMemberButton");

    // 教室管理申請書画面以外で読み込まれても、エラーにならないようにする。
    if (!memberContainer || !addMemberButton) {
        return;
    }

    const maxMemberCount = getMaxMemberCount();

    setupAddMemberButtonEvent();
    updateAddMemberButtonState();

    // =========================================
    // 02. メンバー追加ボタンのイベント設定
    // =========================================

    /*
        「＋ メンバーを追加」ボタンが押されたときに、
        使用メンバー入力欄を1つ追加する。
    */
    function setupAddMemberButtonEvent() {
        addMemberButton.addEventListener("click", function () {
            addMemberInput();
        });
    }

    // =========================================
    // 03. 使用メンバー入力欄の追加処理
    // =========================================

    /*
        現在の入力欄数をもとに、次の使用メンバー入力欄を作成して追加する。
    */
    function addMemberInput() {
        if (isMemberLimitReached()) {
            updateAddMemberButtonState();
            return;
        }

        const nextMemberNumber = getNextMemberNumber();
        const memberInput = createMemberInput(nextMemberNumber);

        memberContainer.appendChild(memberInput);
        updateAddMemberButtonState();
    }

    /*
        次に追加する使用メンバー入力欄の番号を取得する。
    */
    function getNextMemberNumber() {
        return getCurrentMemberCount() + 1;
    }

    /*
        現在表示されている使用メンバー入力欄の数を取得する。
    */
    function getCurrentMemberCount() {
        return memberContainer.querySelectorAll(".member-input").length;
    }

    /*
        使用メンバー入力欄の最大数を取得する。
        Blade側の data-max-members があればそれを優先する。
    */
    function getMaxMemberCount() {
        const maxMembers = Number(memberContainer.dataset.maxMembers);

        if (Number.isInteger(maxMembers) && maxMembers > 0) {
            return maxMembers;
        }

        return 30;
    }

    /*
        使用メンバー入力欄が最大数に達しているか判定する。
    */
    function isMemberLimitReached() {
        return getCurrentMemberCount() >= maxMemberCount;
    }

    /*
        使用メンバー入力欄の input 要素を作成する。
    */
    function createMemberInput(memberNumber) {
        const input = document.createElement("input");

        input.type = "text";
        input.name = "member[]";
        input.className = "member-input";
        input.placeholder = "氏名を入力";
        input.maxLength = 50;
        input.setAttribute("aria-label", `使用メンバー${memberNumber}人目`);

        return input;
    }

    // =========================================
    // 04. メンバー追加ボタンの状態更新
    // =========================================

    /*
        使用メンバー入力欄が最大数に達したら、追加ボタンを押せない状態にする。
    */
    function updateAddMemberButtonState() {
        if (!isMemberLimitReached()) {
            addMemberButton.disabled = false;
            addMemberButton.textContent = "＋ メンバーを追加";
            return;
        }

        addMemberButton.disabled = true;
        addMemberButton.textContent = `最大${maxMemberCount}人まで追加済み`;
    }
});
