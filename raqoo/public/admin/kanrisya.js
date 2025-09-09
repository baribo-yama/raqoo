const screens = {
  dashboardBtn: "dashboardScreen",
  noticeBtn: "noticeScreen",
  historyBtn: "historyScreen"
};

const menuItems = document.querySelectorAll(".menu-item");

menuItems.forEach(item => {
  item.addEventListener("click", () => {
    // メニューの選択状態を更新
    menuItems.forEach(m => m.classList.remove("active"));
    item.classList.add("active");

    // 画面を切り替え
    document.querySelectorAll(".screen").forEach(screen => {
      screen.classList.remove("active");
    });

    const targetScreenId = screens[item.id];
    document.getElementById(targetScreenId).classList.add("active");
  });
});
document.querySelectorAll(".detail-toggle").forEach(button => {
  button.addEventListener("click", () => {
    const currentRow = button.closest("tr");
    const detailRow = currentRow.nextElementSibling;
    const detailCell = detailRow.querySelector(".detail-content");

    const datetime = currentRow.dataset.datetime || "不明";
    const name = currentRow.dataset.name || "不明";
    const purpose = currentRow.dataset.purpose || "不明";

    // 展開するHTMLをセット
    detailCell.innerHTML = `
        <div style="padding: 10px;">
          <p><strong>予約日時:</strong> ${datetime}</p>
          <p><strong>ステータス:</strong>
            <select class="status-select">
              <option value="pending">承認待ち</option>
              <option value="confirmed">確定</option>
            </select>
          </p>
          <p><strong>名前:</strong> ${name}</p>
          <p><strong>予約内容:</strong> ${purpose}</p>
        </div>
      `;

    // 表示/非表示切り替え
    if (detailRow.style.display === "none" || detailRow.style.display === "") {
      detailRow.style.display = "table-row";
    } else {
      detailRow.style.display = "none";
    }
  });
});


// 日付選択時に表示
document.addEventListener('DOMContentLoaded', () => {
  const datePicker = document.getElementById('datePicker');
  const selectedDateSpan = document.getElementById('selectedDate');

  datePicker.addEventListener('change', function() {
    selectedDateSpan.textContent = this.value;
  });

  // ステータス変更時にAPIで登録
  document.querySelectorAll('.status-select').forEach(select => {
    select.addEventListener('change', function() {
      const roomId = this.dataset.room;
      const period = this.dataset.period;
      const status = this.value;
      const date = datePicker.value;

      if (!date) {
        alert('日付を選択してください');
        return;
      }

      fetch('/admin/room-status', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ room_id: roomId, period, status, date })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          console.log('更新成功:', data);
        } else {
          console.error('更新失敗:', data);
        }
      })
      .catch(err => console.error('通信エラー:', err));
    });
  });
});



// csrf-tokenの確認のためのデバッグ
let meta = document.querySelector('meta[name="csrf-token"]');
console.log(meta);
// <meta name="csrf-token" content="xxxxx">
