document.addEventListener('DOMContentLoaded', function () {
    /*
        使用メンバー追加処理
        - 「＋ メンバーを追加」ボタンを押すたびに入力欄を1つ追加
        - 追加した入力欄にも既存と同じ name / class を付ける
    */
    const memberContainer = document.getElementById('member-container');
    const addMemberButton = document.getElementById('addMemberButton');

    if (!memberContainer || !addMemberButton) {
        return;
    }

    addMemberButton.addEventListener('click', function () {
        const input = document.createElement('input');

        input.type = 'text';
        input.name = 'member[]';
        input.className = 'member-input';

        memberContainer.appendChild(input);
    });
});