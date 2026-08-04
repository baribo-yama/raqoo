/*
*セル詳細モーダルの表示/非表示
*/

// モーダルの内容を安全に初期化する関数（textContentでXSS対策）
function clearModalBody(body) {
    body.textContent = '';
}

// モーダル見出しを追加する関数
function appendModalHeading(body, text) {
    const h3 = document.createElement('h3');
    h3.textContent = text;
    body.appendChild(h3);
}

// ラベル付きテキスト行を追加する関数（textContentでXSS対策）
function appendModalTextRow(body, label, value) {
    const p = document.createElement('p');
    p.textContent = `${label}: ${value ?? ''}`;
    body.appendChild(p);
}

// テキストのみの段落を追加する関数（textContentでXSS対策）
function appendModalParagraph(body, value) {
    const p = document.createElement('p');
    p.textContent = value ?? '';
    body.appendChild(p);
}

// モーダル内のボタンを追加し、生成したボタンを返す関数
function appendModalButton(body, id, text) {
    const btn = document.createElement('button');
    btn.id = id;
    btn.textContent = text;
    body.appendChild(btn);
    return btn;
}

// ★ メンバーをチェックボックス付きテーブルで表示する関数
function appendMemberTable(body, representative, membersRaw) {
    // 代表者と全メンバーを1つのリストにまとめる
    const allMembers = [];

    // 代表者を先頭に追加
    if (representative) {
        allMembers.push({ name: representative, isRepresentative: true });
    }

    // membersは改行区切りの文字列なので分割して追加（空行は除外）
    if (membersRaw) {
        const memberNames = membersRaw.split('\n').map(s => s.trim()).filter(s => s !== '');
        memberNames.forEach(name => {
            allMembers.push({ name, isRepresentative: false });
        });
    }

    // テーブルを作成
    const table = document.createElement('table');
    table.className = 'member-checklist-table';

    // ヘッダー行
    const thead = document.createElement('thead');
    thead.innerHTML = `
        <tr>
            <th class="member-checklist-no">No.</th>
            <th class="member-checklist-name">氏名</th>
            <th class="member-checklist-role">役割</th>
            <th class="member-checklist-check">確認</th>
        </tr>
    `;
    table.appendChild(thead);

    // メンバー行
    const tbody = document.createElement('tbody');
    allMembers.forEach((member, index) => {
        const tr = document.createElement('tr');

        const tdNo = document.createElement('td');
        tdNo.className = 'member-checklist-no';
        tdNo.textContent = index + 1;

        const tdName = document.createElement('td');
        tdName.className = 'member-checklist-name';
        tdName.textContent = member.name;

        const tdRole = document.createElement('td');
        tdRole.className = 'member-checklist-role';
        tdRole.textContent = member.isRepresentative ? '代表者' : 'メンバー';

        const tdCheck = document.createElement('td');
        tdCheck.className = 'member-checklist-check';
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.className = 'member-checkbox';
        checkbox.setAttribute('aria-label', `${member.name}の確認`);
        tdCheck.appendChild(checkbox);

        tr.appendChild(tdNo);
        tr.appendChild(tdName);
        tr.appendChild(tdRole);
        tr.appendChild(tdCheck);
        tbody.appendChild(tr);
    });
    table.appendChild(tbody);
    body.appendChild(table);

    // 全チェック/解除ボタン
    const toggleBtn = document.createElement('button');
    toggleBtn.textContent = 'すべてチェック';
    toggleBtn.className = 'member-checklist-toggle-btn';
    toggleBtn.addEventListener('click', () => {
        const checkboxes = table.querySelectorAll('.member-checkbox');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => cb.checked = !allChecked);
        toggleBtn.textContent = allChecked ? 'すべてチェック' : 'すべて解除';
    });
    body.appendChild(toggleBtn);
}

// セルに予約、または授業情報が入ってる、または空の場合に表示させるやつ
function showCellDetailModal(roomId, periodId, date, cell) {
    const modal = document.getElementById('cellDetailModal');
    const overlay = document.getElementById('overlay');
    const body = document.getElementById('cellDetailBody');

    // csrfトークンの定義
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!modal || !overlay || !body) return;

    fetch(`/admin/room-cell-detail?room_id=${roomId}&date=${date}&period=${periodId}`)
        .then(res => {
            if (!res.ok) throw new Error(`Http error: ${res.status}`);
            return res.json();
        })
        .then(data => {

            // セルが空の場合→授業名を登録
            if (data.type === 'empty') {
                body.innerHTML = `
                    <h3>授業名を登録</h3>
                    <form id="lessonForm">
                        <input type="text" name="title" placeholder="授業名">
                        <button type="submit" class="modal-close">登録</button>
                    </form>
                `;

                const form = document.getElementById('lessonForm');
                form?.addEventListener('submit', (e) => {
                    e.preventDefault();
                    const title = form.title.value.trim();
                    if (!title) return;

                    fetch('/admin/room-lesson', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({
                            room_id: roomId,
                            date: date,
                            period: periodId,
                            title: title
                        }),
                    }).then(() => {
                        renderRoomTable();
                    }).catch((error) => {
                        console.error('保存に失敗しました:', error);
                    });
                });

            // ★ 予約がある場合→メンバーチェックボックステーブル＋予約キャンセル
            } else if (data.type === 'reservation') {
                clearModalBody(body);
                appendModalHeading(body, '予約情報');

                // ★ チェックボックス付きメンバーテーブルを表示
                appendMemberTable(body, data.reservation.representative, data.reservation.members);

                const cancelBtn = appendModalButton(body, 'cancelReservationBtn', '予約キャンセル');
                cancelBtn.addEventListener('click', () => {
                    fetch('/admin/reservations/cancel', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({ reservation_id: data.reservation.id }),
                    }).then(() => {
                        renderRoomTable();
                    }).catch((error) => {
                        console.error('予約キャンセルに失敗しました:', error);
                    });
                });

            // 授業がある場合→授業キャンセル
            } else if (data.type === 'lesson') {
                clearModalBody(body);
                appendModalHeading(body, '授業情報');
                appendModalParagraph(body, data.lesson.title);

                const cancelBtn = appendModalButton(body, 'cancelLessonBtn', '授業キャンセル');
                cancelBtn.classList.add('modal-close');
                cancelBtn.addEventListener('click', () => {
                    fetch('/admin/room-lesson/cancel', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({ room_id: roomId, date, period: periodId }),
                    }).then(() => {
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
    const modal = document.getElementById('cellDetailModal');
    const overlay = document.getElementById('overlay');

    if (!modal || !overlay) return;

    modal.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal-close')) {
            modal.classList.remove('active');
            overlay.classList.remove('active');
        }
    });
});