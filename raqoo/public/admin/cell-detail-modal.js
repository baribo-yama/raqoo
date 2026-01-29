/*
*セル詳細モーダルの表示/非表示
*/

// セルに予約、または授業情報が入ってる、または空の場合に表示させるやつ
function showCellDetailModal(roomId, periodId, date, cell) {
    const modal = document.getElementById('cellDetailModal');
    const overlay = document.getElementById('overlay');
    const body = document.getElementById('cellDetailBody');

    if (!modal || !overlay || !body) return;  // 要素かoverlay要素かbody要素のどちらか1つでも見つからなかったら終了する（早期return）

    // ここでAPIを呼んでtypeによってmodalの表示内容を切り替える
    // RoomStatusControllerのgetCellDetail()で処理
    fetch(`/admin/room-cell-detail?room_id=${roomId}&date=${date}&period=${periodId}`)
        .then(res => res.json())  // レスポンスを受け取る
        .then(data => {  // レスポンス内にあるデータを展開して取得
            if (data.type === 'empty') {
                body.innerHTML = `
                    <h3>授業名を登録</h3>
                    <form id="lessonForm">
                        <input type="text" name="title" placeholder="授業名">
                    </form>
                `;
            } else if (data.type === 'reservation') {
                body.innerHTML = `
                    <h3>予約情報</h3>
                    <p>代表者: ${data.reservation.representative ?? ''}</p>
                    <p>メンバー: ${data.reservation.members ?? ''}</p>
                    <button id="cancelReservationBtn">予約キャンセル</button>
                `;
            } else if (data.type === 'lesson') {
                body.innerHTML = `
                    <h3>授業情報</h3>
                    <p>${data.lesson.title}</p>
                    <button id="cancelLessonBtn">授業キャンセル</button>
                `;
            }
        })
        .catch(error => {
            console.error("通信に失敗した", error);
        });

    modal.classList.add('active');
    overlay.classList.add('active');
}

// ページ読み込み時DOMが生成されたときにはじめに読み込まれる
document.addEventListener('DOMContentLoaded', () => {
    // const modalClose = document.getElementById('modalClose');
    const modal = document.getElementById('cellDetailModal');
    const overlay = document.getElementById('overlay');

    if (!modal || !overlay) return;

    // モーダル非表示処理
    // 親要素cellDetailModalにつけてるので、子要素であるmodalCloseにもクリック処理は引き継がれる（今回はidではなくclass=modal-closeを指定）
    modal.addEventListener('click', (e) => {  // eの中には「どの要素がクリックされたか」が入っている（クリックに関する情報のまとまり）
        if (e.target.classList.contains('modal-close')) {   // e.targetは実際にクリックされたDOM要素
            modal.classList.remove('active');
            overlay.classList.remove('active');
        }
    });

});
