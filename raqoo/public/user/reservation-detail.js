document.addEventListener("DOMContentLoaded", function () {
    /*
        reservation-detail.js
        役割：
        - 予約キャンセル確認モーダルの表示 / 非表示

        注意：
        - サイドバー開閉処理は共通JSの home.js に統一する
        - サイドバーの active 表示は Blade 側の route 判定に任せる
    */

    /* =========================================
       01. キャンセル確認モーダル制御
       - 「予約をキャンセル」ボタンでモーダルを表示
       - モーダル内の「キャンセル」ボタンでモーダルを閉じる
    ========================================= */
    const cancelModal = document.getElementById("cancelModal");
    const openCancelModalButton = document.getElementById("openCancelModal");
    const closeCancelModalButton = document.getElementById("closeCancelModal");

    if (cancelModal && openCancelModalButton) {
        openCancelModalButton.addEventListener("click", function () {
            cancelModal.classList.remove("hidden");
            cancelModal.classList.add("flex");
        });
    }

    if (cancelModal && closeCancelModalButton) {
        closeCancelModalButton.addEventListener("click", function () {
            cancelModal.classList.add("hidden");
            cancelModal.classList.remove("flex");
        });
    }
});