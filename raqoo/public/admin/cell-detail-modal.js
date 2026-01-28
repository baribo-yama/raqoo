// セル詳細モーダルの表示/非表示
function showCellDetailModal(roomId, periodId, date, cell) {
  const modal = document.getElementById('cellDetailModal');
  const overlay = document.getElementById('overlay');

  if (!modal || !overlay) return;

  modal.classList.add('active');
  overlay.classList.add('active');
}

document.addEventListener('DOMContentLoaded', () => {
  const modalClose = document.getElementById('modalClose');
  const modal = document.getElementById('cellDetailModal');
  const overlay = document.getElementById('overlay');

  if (modalClose) {
    modalClose.addEventListener('click', () => {
      if (modal) {
        modal.classList.remove('active');
      }
      if (overlay) {
        overlay.classList.remove('active');
      }
    });
  }
});
