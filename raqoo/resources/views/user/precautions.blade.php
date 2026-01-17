@php
  $showAgree = (bool)($showAgree ?? false);
  $next      = $next ?? null;
  $startStep = (int)($startStep ?? 1);
  $mode = $showAgree ? 'confirm' : 'terms';
  if ($startStep < 1 || $startStep > 3) $startStep = 1;
@endphp

<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>注意事項｜3ステップ案内</title>

  {{-- ▼▼▼ 1. home系CSSを読み込む（これが土台） ▼▼▼ --}}
  <link rel="stylesheet" href="{{ asset('user/home.css') }}?v=999">
  <link rel="stylesheet" href="{{ asset('user/home.mobile.css') }}?v=999">
  
  {{-- ▼▼▼ 2. 注意事項ページ専用CSS（中身のカード装飾のみ） ▼▼▼ --}}
  <link rel="stylesheet" href="{{ asset('user/precautions.css') }}?v=999">
  <link rel="stylesheet" href="{{ asset('user/precautions.mobile.css') }}?v=999">

  <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>

<body data-start-step="{{ $startStep }}" data-mode="{{ $mode }}">

  {{-- ▼▼▼ 【重要】home.mobile.css と同じクラス名にする (.m-backdrop) ▼▼▼ --}}
  <div id="mobileOverlay" class="m-backdrop"></div>
  
  <div class="layout">
    
    {{-- サイドバー（home.js がクラスを見て自動で制御します） --}}
    <x-user.sidebar />

    <div class="content-area">
      
      <main class="terms-viewport">
        
        {{-- STEP 1 --}}
        <article class="terms-card" data-step="1" data-active="true" tabindex="0" role="button">
          <div class="terms-kicker">注意事項</div>
          <h1 class="terms-title">3ステップで確認しよう！</h1>
          <div class="terms-panel">
            <div class="terms-badge" aria-hidden="true">1</div>
            <div class="terms-lead">対象と条件を確認する</div>
            <p class="terms-desc">
                利用時間は原則として平日 9:10〜21:30 の授業時間内に限ります。
                学生同士のみの使用は不可（教員同伴必須）。PASS証提示や申請書提出を事前に行う必要あり。
            </p>
          </div>
          <div class="terms-dots">
            <button type="button" class="terms-dot" data-stepjump="1" data-active="true"></button>
            <button type="button" class="terms-dot" data-stepjump="2"></button>
            <button type="button" class="terms-dot" data-stepjump="3"></button>
          </div>
        </article>

        {{-- STEP 2 --}}
        <article class="terms-card" data-step="2" tabindex="0" role="button">
          <div class="terms-kicker">注意事項</div>
          <h1 class="terms-title">3ステップで確認しよう！</h1>
          <div class="terms-panel">
            <div class="terms-badge" aria-hidden="true">2</div>
            <div class="terms-lead">申請書記入と提出</div>
            <p class="terms-desc">
                代表者が氏名・学年・コース・使用目的、
                教室・メンバー名を記入。使用目的は詳細に記載。
                指定場所へ事前に申請書を提出。
            </p>
          </div>
          <div class="terms-dots">
            <button type="button" class="terms-dot" data-stepjump="1"></button>
            <button type="button" class="terms-dot" data-stepjump="2" data-active="true"></button>
            <button type="button" class="terms-dot" data-stepjump="3"></button>
          </div>
        </article>

        {{-- STEP 3 --}}
        <article class="terms-card" data-step="3" tabindex="0" role="button">
          <div class="terms-kicker">注意事項</div>
          <h1 class="terms-title">3ステップで確認しよう！</h1>
          <div class="terms-panel">
            <div class="terms-badge" aria-hidden="true">3</div>
            <div class="terms-lead">利用日当日の対応</div>
            <p class="terms-desc">
                利用開始10分前には受付で手続き。
                申請書のコピーを受け取り、教室の鍵などを受領。
                利用後は速やかに退室し、返却・清掃を行う。
            </p>
          </div>
          <div class="terms-dots">
            <button type="button" class="terms-dot" data-stepjump="1"></button>
            <button type="button" class="terms-dot" data-stepjump="2"></button>
            <button type="button" class="terms-dot" data-stepjump="3" data-active="true"></button>
          </div>
        </article>

        {{-- 同意UI --}}
        @if($showAgree && $next)
          <div class="agree-wrap" id="agreeSection" aria-hidden="true">
            <form method="POST" action="{{ route('user.precautions.agree') }}" class="agree-form">
              @csrf
              <input type="hidden" name="next" value="{{ $next }}">
              <label class="skip-check">
                <input type="checkbox" name="skip_next_time" value="1">
                次回からこの注意事項を表示しない
              </label>
              <button type="submit" class="agree-btn" id="agreeBtn" disabled>
                同意して予約へ進む
              </button>
            </form>
          </div>
        @endif

      </main>
    </div>
  </div>

  {{-- ▼▼▼ 3. home.js を読み込む（これがメニュー制御の本体です） ▼▼▼ --}}
  <script src="{{ asset('user/home.js') }}?v=999"></script>

  {{-- ▼▼▼ 4. precautions.js (3ステップの制御のみ) ▼▼▼ --}}
  <script src="{{ asset('user/precautions.js') }}?v=999"></script>
</body>
</html>