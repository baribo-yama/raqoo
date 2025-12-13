// DOM読み込み完了後に全ての初期化処理を実行
document.addEventListener('DOMContentLoaded', function () {
    // 曜日タブのクリック処理
    document.querySelectorAll('.day-tab').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.day-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // タイムカードのクリック処理
    document.querySelectorAll('.time-card').forEach(card => {
        card.addEventListener('click', function () {
            // 「利用不可」はスルー
            if (card.classList.contains('unavailable')) return;

            // すべてのactiveを外す
            document.querySelectorAll('.time-card.active').forEach(activeCard => {
                activeCard.classList.remove('active');
            });
            // クリックしたカードにactiveを付与
            card.classList.add('active');
        });
    });

    // ナビゲーション機能
    const list = document.querySelectorAll(".list");

    function activeLink() {
        list.forEach(item => item.classList.remove("active"));
        this.classList.add("active");
    }

    list.forEach(item => item.addEventListener("click", activeLink));

    // Mobile sidebar toggle
    const hamburger = document.querySelector('.hamburger-btn');
    const overlay = document.createElement('div');
    overlay.className = 'nav-overlay';
    document.body.appendChild(overlay);

    function openNav() {
        document.body.classList.add('nav-visible');
        if (hamburger) hamburger.setAttribute('aria-expanded', 'true');
    }

    function closeNav() {
        document.body.classList.remove('nav-visible');
        if (hamburger) hamburger.setAttribute('aria-expanded', 'false');
    }

    if (hamburger) {
        hamburger.addEventListener('click', function (e) {
            e.stopPropagation();
            if (document.body.classList.contains('nav-visible')) {
                closeNav();
            } else {
                openNav();
            }
        });
    }

    // close via overlay click
    overlay.addEventListener('click', function () { closeNav(); });

    // close on ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && document.body.classList.contains('nav-visible')) {
            closeNav();
        }
    });
});
