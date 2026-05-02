document.addEventListener('DOMContentLoaded', function () {
    /*
        キャンセル確認モーダル制御
        - 「予約をキャンセル」ボタンを押すとモーダルを表示
        - モーダル内の「キャンセル」ボタンを押すとモーダルを閉じる
    */
    const cancelModal = document.getElementById('cancelModal');
    const openCancelModalButton = document.getElementById('openCancelModal');
    const closeCancelModalButton = document.getElementById('closeCancelModal');

    if (cancelModal && openCancelModalButton) {
        openCancelModalButton.addEventListener('click', function () {
            cancelModal.classList.remove('hidden');
            cancelModal.classList.add('flex');
        });
    }

    if (cancelModal && closeCancelModalButton) {
        closeCancelModalButton.addEventListener('click', function () {
            cancelModal.classList.add('hidden');
            cancelModal.classList.remove('flex');
        });
    }

    /*
        ナビゲーションのアクティブ状態切り替え
        - サイドバー項目をクリックしたときに active クラスを付け替える
    */
    const listItems = document.querySelectorAll('.list');

    listItems.forEach(function (item) {
        item.addEventListener('click', function () {
            listItems.forEach(function (listItem) {
                listItem.classList.remove('active');
            });

            item.classList.add('active');
        });
    });

    /*
        トグルメニュー制御
        - .toggle が存在する場合のみ実行
        - .navigation と .layout の開閉状態を切り替える
    */
    const toggle = document.querySelector('.toggle');
    const navigation = document.querySelector('.navigation');
    const layout = document.querySelector('.layout');

    if (toggle && navigation && layout) {
        toggle.addEventListener('click', function () {
            navigation.classList.toggle('active');
            layout.classList.toggle('nav-open');
        });
    }
});