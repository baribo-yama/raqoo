
//曜日クリックで色変わる変数
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.day-tab').forEach(tab => {
    tab.addEventListener('click', function() {
      document.querySelectorAll('.day-tab').forEach(t => t.classList.remove('active'));
      this.classList.add('active');
    });
  });
});
document.querySelectorAll('.time-card').forEach(card => {
  card.addEventListener('click', function() {
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
