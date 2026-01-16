// ナビゲーション（サイドバー）の選択状態切り替え
let list = document.querySelectorAll('.list');
function activeLink() {
  list.forEach((item) => item.classList.remove('active'));
  this.classList.add('active');
}
list.forEach((item) => item.addEventListener('click', activeLink));

// ハンバーガーメニューの開閉トグル
let toggle = document.querySelector('.toggle');
let navigation = document.querySelector('.navigation');
let main = document.querySelector('.main-content');

if(toggle && navigation && main){
  toggle.onclick = function() {
    navigation.classList.toggle('active');
    main.classList.toggle('active');
  }
}