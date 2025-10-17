<html>
<head>
  <meta charset="utf-8">
  <title>Long Polling テスト</title>
  <style>
    body{font-family:system-ui, sans-serif; padding:20px;}
    #log{border:1px solid #ddd; padding:10px; height:300px; overflow:auto; background:#f9f9f9;}
    #controls{margin-top:12px;}
  </style>
</head>
<body>
  <h2>Long Polling テストページ</h2>
  <p>このページを複数のタブで開いて、片方で「Send」を押すと他のタブが受信します。</p>

  <div id="log"></div>

  <div id="controls">
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

  // 最後に受信したメッセージID。初回は 0。
  let lastId = 0;

  function appendLine(text) {
    const d = document.createElement('div');
    d.textContent = new Date().toLocaleTimeString() + ' — ' + text;
    logEl.appendChild(d);
    logEl.scrollTop = logEl.scrollHeight;
  }

  async function longPoll() {
    try {
  const url = '/long-poll/receive?last_id=' + lastId;
    const res = await fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' }});
    const data = await res.json();

      if (data.ok && data.messages && data.messages.length) {
      data.messages.forEach(m=>{
        appendLine('[受信] ' + m.id + ': ' + m.text + ' (at ' + m.created_at + ')');
      });
        lastId = data.last_id || lastId;
    } else {
      appendLine('[待機] タイムアウト or 新着なし');
    }

    longPoll();
  } catch (err) {
    appendLine('[エラー] ' + err);
    setTimeout(longPoll, 2000);
  }
  }

  // 送信側の処理
  sendBtn.addEventListener('click', async ()=>{
    const txt = input.value || 'テスト';
  try {
    const res = await fetch('/long-poll/send', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({ text: txt })
    });
    const j = await res.json();
    if (j.ok) {
      appendLine('[送信成功] ' + txt);
      // 楽観UI: すぐログに表示（ID等はサーバ返却のものを使っても良い）
    } else {
      appendLine('[送信失敗] ' + (j.error || '不明なエラー'));
    }
  } catch (e) {
    appendLine('[送信エラー] ' + e);
  }
    });

  clearBtn.addEventListener('click', ()=> {
    logEl.innerHTML = '';
  });

  // ページ読み込みたら長ポーリング開始
  appendLine('開始: 長ポーリング接続を開始します');
  longPoll();
})();
</script>
<meta name="csrf-token" content="{{ csrf_token() }}">
</body>
</html>
