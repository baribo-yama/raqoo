// ナビゲーション（サイドバー）の選択状態切り替えのみ残す
// ※ハンバーガーメニューの開閉処理は sidebar コンポーネントの機能を使用するため削除
// (Duplicates functionality fix)

document.addEventListener('DOMContentLoaded', function() {
    let list = document.querySelectorAll('.list');
    
    function activeLink() {
        list.forEach((item) => item.classList.remove('active'));
        this.classList.add('active');
    }
    
    if(list.length > 0) {
        list.forEach((item) => item.addEventListener('click', activeLink));
    }
});