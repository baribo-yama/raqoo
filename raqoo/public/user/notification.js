// サイドバーの「選択状態(active)」だけをURLで自動反映する
// ※開閉(nav-open)には一切触らない

document.addEventListener('DOMContentLoaded', () => {
  const items = document.querySelectorAll('.navigation .list');
  if (!items.length) return;

  const currentPath = location.pathname.replace(/\/+$/, ""); // 末尾スラッシュ統一

  // いったん全部外す
  items.forEach(li => li.classList.remove('active'));

  // a[href] を見て「今のURLと一致 or 前方一致」したやつを active にする
  let matched = null;

  items.forEach(li => {
    const a = li.querySelector('a[href]');
    if (!a) return;

    // 絶対URL/相対URLどっちでも対応
    let hrefPath = "";
    try {
      hrefPath = new URL(a.getAttribute('href'), location.origin).pathname.replace(/\/+$/, "");
    } catch {
      return;
    }

    // 完全一致を最優先、次に前方一致（/reservations/123 みたいなの）
    if (currentPath === hrefPath) {
      matched = li;
    } else if (!matched && hrefPath !== "" && currentPath.startsWith(hrefPath)) {
      matched = li;
    }
  });

  if (matched) matched.classList.add('active');
});
