/*
    サイドバーの選択状態を現在のURLから自動反映する処理

    役割：
    - 現在開いているページのURLを取得する
    - サイドバー内のリンクURLと比較する
    - 一致するメニュー項目に active クラスを付ける

    注意：
    - サイドバーの開閉状態 nav-open には触らない
    - 見た目の切り替えは active クラスだけで行う
*/

document.addEventListener("DOMContentLoaded", () => {
    /* =========================================
       01. サイドバー項目を取得
    ========================================= */
    const sidebarItems = document.querySelectorAll(".navigation .list");

    if (!sidebarItems.length) {
        return;
    }


    /* =========================================
       02. 現在のURLパスを取得
       - 末尾の / は比較しやすいように削除
       - 例：/user/reservations/ → /user/reservations
    ========================================= */
    const currentPath = location.pathname.replace(/\/+$/, "");


    /* =========================================
       03. 既存の active クラスを一度すべて外す
    ========================================= */
    sidebarItems.forEach((item) => {
        item.classList.remove("active");
    });


    /* =========================================
       04. 現在のURLに一致するメニュー項目を探す
       - 完全一致を優先
       - 完全一致がなければ前方一致で判定
       - 詳細ページでも親メニューを active にできる
    ========================================= */
    let matchedItem = null;

    sidebarItems.forEach((item) => {
        const link = item.querySelector("a[href]");

        if (!link) {
            return;
        }

        let linkPath = "";

        try {
            linkPath = new URL(link.getAttribute("href"), location.origin)
                .pathname
                .replace(/\/+$/, "");
        } catch {
            return;
        }

        // 完全一致を最優先
        if (currentPath === linkPath) {
            matchedItem = item;
            return;
        }

        // 詳細ページなどは前方一致で親メニューを選択状態にする
        if (!matchedItem && linkPath !== "" && currentPath.startsWith(linkPath)) {
            matchedItem = item;
        }
    });


    /* =========================================
       05. 一致したメニュー項目を active にする
    ========================================= */
    if (matchedItem) {
        matchedItem.classList.add("active");
    }
});