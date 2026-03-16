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
            <div class="terms-lead">空き時間を確認・選択する</div>
            <p class="terms-desc">
              教室カレンダーから希望の日付・時限を選択してください。<br>
              申請可能な時間帯は、平日 9:10〜19:30、土日 10:50〜17:50（授業時間内）に限ります。<br>
              また、２名以上での利用の場合、１教室につき２コマまで借りることができます。
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
            <div class="terms-lead">申請フォームを入力して送信する</div>
            <p class="terms-desc">
              利用目的・代表者・利用メンバー（氏名と学籍番号）を入力し申請を送信。<br>
              管理者が確認後、承認または却下というメッセージがアプリの「お知らせ」に届きます。
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
            <div class="terms-lead">当日・受け取りと返却</div>
            <p class="terms-desc">
              利用開始10分前に受付で手続きし、教室の鍵などを受領。<br>
              また、鍵の受け取り時には全員分の学生証の提出が必要です。<br>
              学生証は鍵の返却時にお返しします。<br>
              利用後は速やかに退室し、返却・清掃を行ってください。
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
