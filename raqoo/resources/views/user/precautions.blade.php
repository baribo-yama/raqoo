<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>夜間注意事項｜3ステップ案内</title>
  <link rel="stylesheet" href="{{ asset('user/precautions.css') }}">
</head>
<body>
  <main class="viewport">
    <!-- STEP 1 -->
    <article class="card" data-step="1" data-active="true">
      <div class="card-kicker">注意事項</div>
      <h1 class="card-title">3ステップで確認しよう！</h1>

      <div class="panel">
        <button class="badge" data-stepjump="1" aria-label="ステップ1へ" tabindex="-1">1</button>
        <div class="lead">対象と条件を確認する</div>
        <p class="desc">
利用時間は 金曜 19:40 ～ 翌日 6:00 のみ。
学生同士のみの使用は不可（教員同伴必須）。
PASS証提示 や 申請書提出 を事前に行う必要あり。
        </p>
      </div>

      <div class="dots" aria-label="手順ナビ（ドット）">
        <button class="dot" data-stepjump="1" data-active="true" aria-label="ステップ1へ"></button>
        <button class="dot" data-stepjump="2" aria-label="ステップ2へ"></button>
        <button class="dot" data-stepjump="3" aria-label="ステップ3へ"></button>
      </div>
    </article>

    <!-- STEP 2 -->
    <article class="card" data-step="2">
      <div class="card-kicker">注意事項</div>
      <h1 class="card-title">3ステップで確認しよう！</h1>

      <div class="panel">
        <button class="badge" data-stepjump="2" aria-label="ステップ2へ" tabindex="-1">2</button>
        <div class="lead">申請書記入と提出</div>
        <p class="desc">
代表者が氏名・学年・コース・使用目的
教室・メンバー名を記入。  使用目的は詳細に記載。
指定場所へ事前に申請書を提出。
        </p>
      </div>

      <div class="dots" aria-label="手順ナビ（ドット）">
        <button class="dot" data-stepjump="1" aria-label="ステップ1へ"></button>
        <button class="dot" data-stepjump="2" data-active="true" aria-label="ステップ2へ"></button>
        <button class="dot" data-stepjump="3" aria-label="ステップ3へ"></button>
      </div>
    </article>

    <!-- STEP 3 -->
    <article class="card" data-step="3">
      <div class="card-kicker">注意事項</div>
      <h1 class="card-title">3ステップで確認しよう！</h1>

      <div class="panel">
        <button class="badge" data-stepjump="3" aria-label="ステップ3へ" tabindex="-1">3</button>
        <div class="lead">利用日当日の対応</div>
        <p class="desc">
利用開始10分前には受付で手続き。
申請書のコピーを受け取り、教室の鍵などを受領。
利用後は速やかに退室し、返却・清掃を行う。        </p>
      </div>

      <div class="dots" aria-label="手順ナビ（ドット）">
        <button class="dot" data-stepjump="1" aria-label="ステップ1へ"></button>
        <button class="dot" data-stepjump="2" aria-label="ステップ2へ"></button>
        <button class="dot" data-stepjump="3" data-active="true" aria-label="ステップ3へ"></button>
      </div>
    </article>
  </main>

  <script src="{{ asset('user/precautions.js') }}"></script>
</body>
</html>
