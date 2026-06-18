/*
    お知らせ画面用JavaScript

    役割：
    - 現在URLに合わせてサイドバーの active を付け直す
    - スマホ表示時のサイドバー開閉を行う

    注意：
    - お知らせ一覧の取得・既読処理・削除処理は行わない
    - ホーム画面専用の予約ステータス取得処理は行わない
*/

document.addEventListener("DOMContentLoaded", () => {
    setupMobileSidebar();
    updateActiveSidebarItem();
});

/*
    スマホ表示時のサイドバー開閉を設定する。
    home.js を読み込まなくても、お知らせ画面だけで開閉できるようにする。
*/
function setupMobileSidebar() {
    const sidebarOpenButtons = document.querySelectorAll(
        ".nav-toggle, .m-hamburger",
    );
    const backdrop = document.querySelector(".m-backdrop");

    for (const sidebarOpenButton of sidebarOpenButtons) {
        sidebarOpenButton.addEventListener("click", () => {
            openSidebar();
        });
    }

    if (backdrop) {
        backdrop.addEventListener("click", () => {
            closeSidebar();
        });
    }

    document.addEventListener("keydown", (event) => {
        if (event.key !== "Escape") {
            return;
        }

        closeSidebar();
    });
}

/*
    サイドバーを開く。
    CSS側は body.nav-open を見て表示を切り替える。
*/
function openSidebar() {
    document.body.classList.add("nav-open");
}

/*
    サイドバーを閉じる。
*/
function closeSidebar() {
    document.body.classList.remove("nav-open");
}

/*
    サイドバーの active 状態を現在URLに合わせて更新する。
    サーバー側や別JSで付いた active が残っていても、一度すべて外してから付け直す。
*/
function updateActiveSidebarItem() {
    const sidebarItems = getSidebarItems();

    if (sidebarItems.length === 0) {
        return;
    }

    const currentPath = getNormalizedCurrentPath();

    clearAllSidebarActiveClasses();

    const activeSidebarItem = findActiveSidebarItem(sidebarItems, currentPath);

    if (!activeSidebarItem) {
        return;
    }

    activeSidebarItem.classList.add("active");
}

/*
    サイドバー内のメニュー項目を取得する。
    li / .list のどちらのHTML構造でも動くように、リンクから親要素を探す。
*/
function getSidebarItems() {
    const sidebarLinks = document.querySelectorAll(".navigation a[href]");
    const sidebarItems = [];

    for (const sidebarLink of sidebarLinks) {
        const sidebarItem =
            sidebarLink.closest("li") || sidebarLink.closest(".list");

        if (!sidebarItem) {
            continue;
        }

        if (sidebarItems.includes(sidebarItem)) {
            continue;
        }

        sidebarItems.push(sidebarItem);
    }

    return sidebarItems;
}

/*
    現在開いているページのパスを取得する。
    末尾の / は比較のズレを防ぐため削除する。
*/
function getNormalizedCurrentPath() {
    return normalizePath(location.pathname);
}

/*
    サイドバー内に残っている active をすべて外す。
    予約一覧などに active が残る問題をここで防ぐ。
*/
function clearAllSidebarActiveClasses() {
    const activeElements = document.querySelectorAll(".navigation .active");

    for (const activeElement of activeElements) {
        activeElement.classList.remove("active");
    }
}

/*
    現在URLに対応するサイドバー項目を探す。
    完全一致を優先し、完全一致がなければ親ページ判定を行う。
*/
function findActiveSidebarItem(sidebarItems, currentPath) {
    const exactMatchedItem = findExactMatchedSidebarItem(
        sidebarItems,
        currentPath,
    );

    if (exactMatchedItem) {
        return exactMatchedItem;
    }

    return findParentMatchedSidebarItem(sidebarItems, currentPath);
}

/*
    現在ページとリンク先が完全に同じサイドバー項目を探す。
*/
function findExactMatchedSidebarItem(sidebarItems, currentPath) {
    for (const sidebarItem of sidebarItems) {
        const linkPath = getSidebarLinkPath(sidebarItem);

        if (!linkPath) {
            continue;
        }

        if (currentPath === linkPath) {
            return sidebarItem;
        }
    }

    return null;
}

/*
    詳細ページなどで、親メニューとして扱えるサイドバー項目を探す。
*/
function findParentMatchedSidebarItem(sidebarItems, currentPath) {
    let parentMatchedItem = null;
    let longestMatchedPathLength = 0;

    for (const sidebarItem of sidebarItems) {
        const linkPath = getSidebarLinkPath(sidebarItem);

        if (!linkPath) {
            continue;
        }

        if (!isChildPage(currentPath, linkPath)) {
            continue;
        }

        if (linkPath.length > longestMatchedPathLength) {
            parentMatchedItem = sidebarItem;
            longestMatchedPathLength = linkPath.length;
        }
    }

    return parentMatchedItem;
}

/*
    サイドバー項目のリンクURLを比較用のパスに変換する。
*/
function getSidebarLinkPath(sidebarItem) {
    const sidebarLink = sidebarItem.querySelector("a[href]");

    if (!sidebarLink) {
        return null;
    }

    const href = sidebarLink.getAttribute("href");

    if (!href) {
        return null;
    }

    try {
        const linkUrl = new URL(href, location.origin);
        return normalizePath(linkUrl.pathname);
    } catch {
        return null;
    }
}

/*
    パス比較用に末尾の / を削除する。
*/
function normalizePath(path) {
    return path.replace(/\/+$/, "");
}

/*
    現在ページが、指定リンクの配下ページかどうかを判定する。

    startsWith だけだと、
    /reservations-old が /reservations に一致する可能性がある。
    そのため、linkPath + "/" で配下ページだけを判定する。
*/
function isChildPage(currentPath, linkPath) {
    if (linkPath === "") {
        return false;
    }

    return currentPath.startsWith(`${linkPath}/`);
}
