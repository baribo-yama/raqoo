document.addEventListener("DOMContentLoaded", function () {
    /*
        reservation-detail.js

        役割：
        - 予約詳細画面のキャンセル確認モーダルを表示する
        - 予約詳細画面のキャンセル確認モーダルを閉じる

        注意：
        - このファイルでは予約詳細画面の処理だけを扱う
        - サイドバー開閉処理は扱わない
        - 予約情報の取得・更新・削除処理は扱わない
    */

    // =========================================
    // 01. キャンセル確認モーダルで使うHTML要素
    // =========================================

    const cancelModal = document.getElementById("cancelModal");
    const openCancelModalButton = document.getElementById("openCancelModal");
    const closeCancelModalButton = document.getElementById("closeCancelModal");

    // 予約詳細画面以外でこのJSが読み込まれても、エラーにならないようにする。
    if (!cancelModal) {
        return;
    }

    setupCancelModalEvents();

    // =========================================
    // 02. キャンセル確認モーダルのイベント設定
    // =========================================

    /*
        予約キャンセル確認モーダルの開閉イベントをまとめて設定する。
    */
    function setupCancelModalEvents() {
        if (openCancelModalButton) {
            openCancelModalButton.addEventListener("click", function () {
                openCancelModal();
            });
        }

        if (closeCancelModalButton) {
            closeCancelModalButton.addEventListener("click", function () {
                closeCancelModal();
            });
        }

        cancelModal.addEventListener("click", function (event) {
            closeCancelModalWhenBackdropClicked(event);
        });

        document.addEventListener("keydown", function (event) {
            closeCancelModalWhenEscapePressed(event);
        });
    }

    // =========================================
    // 03. キャンセル確認モーダルの表示・非表示
    // =========================================

    /*
        予約キャンセル確認モーダルを表示する。
    */
    function openCancelModal() {
        cancelModal.classList.remove("hidden");
        cancelModal.classList.add("flex");
        cancelModal.setAttribute("aria-hidden", "false");
    }

    /*
        予約キャンセル確認モーダルを非表示にする。
    */
    function closeCancelModal() {
        cancelModal.classList.add("hidden");
        cancelModal.classList.remove("flex");
        cancelModal.setAttribute("aria-hidden", "true");
    }

    /*
        モーダルの背景部分をクリックしたときだけ閉じる。
        モーダル内の確認ボックスをクリックした場合は閉じない。
    */
    function closeCancelModalWhenBackdropClicked(event) {
        if (event.target !== cancelModal) {
            return;
        }

        closeCancelModal();
    }

    /*
        Escapeキーが押されたとき、表示中のキャンセル確認モーダルを閉じる。
    */
    function closeCancelModalWhenEscapePressed(event) {
        if (event.key !== "Escape") {
            return;
        }

        if (cancelModal.classList.contains("hidden")) {
            return;
        }

        closeCancelModal();
    }
});
