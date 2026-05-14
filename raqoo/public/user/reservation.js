document.addEventListener("DOMContentLoaded", function () {
    /*
        reservation.js

        役割：
        - 「＋ メンバーを追加」ボタンを押したときに入力欄を1つ追加する
        - 追加した入力欄にも既存と同じ name / class を付ける
        - アクセシビリティ用に aria-label も設定する
    */


    /* =========================================
       01. DOM要素取得
       - メンバー入力欄を追加する親要素
       - メンバー追加ボタン
    ========================================= */
    const memberContainer = document.getElementById("member-container");
    const addMemberButton = document.getElementById("addMemberButton");

    if (!memberContainer || !addMemberButton) {
        return;
    }


    /* =========================================
       02. メンバー入力欄追加処理
       - 現在の入力欄数を数える
       - 次の番号を aria-label に設定する
       - 作成した input を memberContainer に追加する
    ========================================= */
    addMemberButton.addEventListener("click", function () {
        const currentMemberCount = memberContainer.querySelectorAll(".member-input").length;
        const nextMemberNumber = currentMemberCount + 1;

        const input = document.createElement("input");

        input.type = "text";
        input.name = "member[]";
        input.className = "member-input";
        input.setAttribute("aria-label", `使用メンバー${nextMemberNumber}人目`);

        memberContainer.appendChild(input);
    });
});