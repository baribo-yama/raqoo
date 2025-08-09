// Ioniconsサイドバーのactive切替
document.querySelectorAll('.list').forEach(item => {
  item.addEventListener('click', function () {
    document.querySelectorAll('.list').forEach(i => i.classList.remove('active'));
    this.classList.add('active');
  });
});
