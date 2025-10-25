<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Long Poll User</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    body{font-family:system-ui, sans-serif; padding:20px;}
    #log{border:1px solid #ddd; padding:10px; height:300px; overflow:auto; background:#f9f9f9;}
    #controls{margin-top:12px;}
  </style>
</head>
<body>
  <h2>利用者ページ（B）</h2>
  <p>ここから名前とメッセージを送ると、管理者がOKを返してくれます（OKはこのタブだけ受信されます）。</p>

  <div id="log"></div>

  <div id="controls">
    <input id="name" placeholder="名前" value="名無し" />
    <input id="msg" placeholder="メッセージ入力" value="こんにちは！" />
    <button id="sendBtn">Send</button>
    <button id="clearBtn">Clear Log</button>
  </div>

<script>
(function(){
  const logEl = document.getElementById('log');
  const sendBtn = document.getElementById('sendBtn');
  const clearBtn = document.getElementById('clearBtn');
  const input = document.getElementById('msg');
  const nameInput = document.getElementById('name');

  // client_id を localStorage に保持（タブ再読み込みしても同じ client_id）
  let clientId = localStorage.getItem('client_id');
  if (!clientId) {
    clientId = 'c_' + Math.random().toString(36).slice(2);
    localStorage.setItem('client_id', clientId);
  }

  let lastId = 0;

  function appendLine(text) {
    const d = document.createElement('div');
    d.textContent = new Date().toLocaleTimeString() + ' — ' + text;
    logEl.appendChild(d);
    logEl.scrollTop = logEl.scrollHeight;
  }

  async function longPoll() {
    try {
      const url = '/long-poll/receive?last_id=' + lastId + '&role=user&client_id=' + encodeURIComponent(clientId);
      const res = await fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' }});
      const data = await res.json();

      if (data.ok && data.messages && data.messages.length) {
        data.messages.forEach(m=>{
          appendLine('[受信 ACK] ' + m.id + ': ' + m.text + ' (ref #' + m.ref_id + ')');
        });
        lastId = data.last_id || lastId;
      } else {
        // タイムアウト等
      }

      longPoll();
    } catch (err) {
      appendLine('[エラー] ' + (err.message || err));
      setTimeout(longPoll, 2000);
    }
  }

  // 送信
  sendBtn.addEventListener('click', async ()=>{
    const txt = input.value || 'テスト';
    const name = nameInput.value || '名無し';

    try {
      const res = await fetch('/long-poll/send', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          role: 'user',
          name: name,
          text: txt,
          client_id: clientId
        })
      });
      const j = await res.json();
      if (j.ok) {
        appendLine('[送信成功] ' + txt + ' (id #' + j.last_id + ')');
        // 楽観表示：すぐに画面に出したいならここで appendLine する
      } else {
        appendLine('[送信失敗] ' + (j.error || '不明なエラー'));
      }
    } catch (e) {
      appendLine('[送信エラー] ' + (e.message || e));
    }
  });

  clearBtn.addEventListener('click', ()=> {
    logEl.innerHTML = '';
  });

  appendLine('開始: client_id=' + clientId);
  appendLine('長ポーリング開始...');
  longPoll();
})();
</script>
</body>
</html>
