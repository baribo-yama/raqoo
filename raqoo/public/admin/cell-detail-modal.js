/*
*セル詳細モーダルの表示/非表示
*/

// セルに予約、または授業情報が入ってる、または空の場合に表示させるやつ
function showCellDetailModal(roomId, periodId, date, cell) {
    const modal = document.getElementById('cellDetailModal');
    const overlay = document.getElementById('overlay');
    const body = document.getElementById('cellDetailBody');

    // csrfトークンの定義
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!modal || !overlay || !body) return;  // 要素かoverlay要素かbody要素のどちらか1つでも見つからなかったら終了する（早期return）

    // ここでAPIを呼んでtypeによってmodalの表示内容を切り替える
    // RoomStatusControllerのgetCellDetail()で処理
    fetch(`/admin/room-cell-detail?room_id=${roomId}&date=${date}&period=${periodId}`)
        .then(res => res.json())  // レスポンスを受け取る
        .then(data => {  // レスポンス内にあるデータを展開して取得

            // セルが空の場合→授業名を登録
            if (data.type === 'empty') {
                body.innerHTML = `
                    <h3>授業名を登録</h3>
                    <form id="lessonForm">
                        <input type="text" name="title" placeholder="授業名">
                        <button type="submit">登録</button>
                    </form>
                `;

                // 1. フォームの要素を取得（? は、もしフォームがなくてもエラーにしない安全装置）
                const form = document.getElementById('lessonForm');

                form?.addEventListener('submit', (e) => {
                    // 2. 【超重要】ブラウザが勝手にページをリロードするのを止める
                    // これがないと、fetchの返事が来る前にページが真っ白になって処理が途切れてしまう
                    e.preventDefault();

                    // 3. 入力された「授業名」を取り出し、前後の無駄な空白を消す
                    const title = form.title.value.trim();

                    // 4. もし授業名が空っぽだったら、これ以上先の処理（サーバーへの送信）はしない
                    if (!title) return;

                    // 5. サーバー（Laravel）に非同期で「保存して！」と注文を出す
                    fetch('/admin/room-lesson', {
                        method: 'POST', // データの新規作成・送信なのでPOST
                        headers: {
                            'Content-Type': 'application/json', // 「JSON形式のデータを送るよ」という宣言
                            'X-CSRF-TOKEN': csrf, // Laravelが「正規の送信だ」と判断するためのセキュリティトークン
                        },
                        // 6. サーバーが受け取れるように、連想配列をJSONという文字列に変換して送る
                        body: JSON.stringify({  // キーと名前が同じなら省略しても書ける→ body: JSON.stringify({ room_id: roomId, date, period: periodId, title}),
                            room_id: roomId,
                            date: date,
                            period: periodId,
                            title: title
                        }),
                    }).then(() => {
                        // 7. サーバーから無事に「成功」の返事が届いた後の処理
                        // closeModal();         // 入力フォーム（モーダル）を閉じる
                        renderRoomTable();    // ページをリロードせずに、時間割テーブルを最新の状態に更新する
                    }).catch((error) => {
                        // （おまけ）通信エラーなどが起きた場合の予備処理
                        console.error('保存に失敗しました:', error);
                    });
                });

            // 予約がある場合→予約キャンセル
            } else if (data.type === 'reservation') {
                body.innerHTML = `
                    <h3>予約情報</h3>
                    <p>代表者: ${data.reservation.representative ?? ''}</p>
                    <p>メンバー: ${data.reservation.members ?? ''}</p>
                    <button id="cancelReservationBtn">予約キャンセル</button>
                `;

                document.getElementById('cancelReservationBtn')
                    ?.addEventListener('click', () => {
                        fetch('/admin/reservations/cancel', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                        body: JSON.stringify({ reservation_id: data.reservation.id }),
                    }).then(() => {
                        // closeModal();　TODO:授業登録時、キャンセル時に自動でモーダルを非表示にしたい場合は別途でcloseModal()を実装する必要あり
                        renderRoomTable();
                    }).catch((error) => {
                        console.error('予約キャンセルに失敗しました:', error);
                    });
                });

            // 授業がある場合→授業キャンセル
            } else if (data.type === 'lesson') {
                body.innerHTML = `
                    <h3>授業情報</h3>
                    <p>${data.lesson.title}</p>
                    <button id="cancelLessonBtn">授業キャンセル</button>
                `;

                document.getElementById('cancelLessonBtn')
                    ?.addEventListener('click', () => {
                        fetch('/admin/room-lesson/cancel', {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                            },
                        body: JSON.stringify({ room_id: roomId, date, period: periodId }),
                    }).then(() => {
                        // closeModal();
                        renderRoomTable();
                    }).catch((error) => {
                        console.error('授業キャンセルに失敗しました:', error);
                    });
                });
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
