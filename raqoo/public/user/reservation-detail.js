document.addEventListener("DOMContentLoaded", function () {
    /*
        reservation-detail.js
        役割：
        - 予約キャンセル確認モーダルの表示 / 非表示
        - サイドバー項目の active 切り替え
        - トグルメニューの開閉制御

        注意：
        - サイドバー制御は共通JSと重複する可能性があるため、
          将来的には共通JS側へ寄せる候補
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


    /* =========================================
       02. サイドバーの active 状態切り替え
       - サイドバー項目をクリックしたときに active クラスを付け替える
       - URL連動のactive制御JSが別にある場合は重複注意
    ========================================= */
    const listItems = document.querySelectorAll(".list");

    listItems.forEach(function (item) {
        item.addEventListener("click", function () {
            listItems.forEach(function (listItem) {
                listItem.classList.remove("active");
            });

            item.classList.add("active");
        });
    });


    /* =========================================
       03. トグルメニュー制御
       - .toggle が存在する場合のみ実行
       - .navigation と .layout の開閉状態を切り替える
    ========================================= */
    const toggle = document.querySelector(".toggle");
    const navigation = document.querySelector(".navigation");
    const layout = document.querySelector(".layout");

    if (toggle && navigation && layout) {
        toggle.addEventListener("click", function () {
            navigation.classList.toggle("active");
            layout.classList.toggle("nav-open");
        });
    }
});