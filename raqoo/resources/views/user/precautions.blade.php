@php
  // ★修正: キャストを厳密に
  $showAgree = (bool)($showAgree ?? false);
  $next      = $next ?? null;
  $startStep = (int)($startStep ?? 1);

  // data-mode を必ず "confirm" or "terms" の2択に固定
  $mode = $showAgree ? 'confirm' : 'terms';

  // startStep の安全化（1〜3）
  if ($startStep < 1 || $startStep > 3) $startStep = 1;
@endphp

<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>注意事項｜3ステップ案内</title>

  {{-- サイドバー&共通 --}}
  <link rel="stylesheet" href="{{ asset('user/home.css') }}">

  {{-- 3ステップ用 --}}
  <link rel="stylesheet" href="{{ asset('user/precautions.css') }}">

  {{-- ion-icon --}}
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>

<body
  data-start-step="{{ $startStep }}"
  data-mode="{{ $mode }}"
>
  <div class="main-content">
    <x-user.sidebar />

    <div class="content-area">
      <main class="terms-viewport">

        {{-- STEP 1 --}}
        <article class="terms-card" data-step="1" data-active="true" tabindex="0" role="button" aria-label="ステップ1：対象と条件を確認する（クリックまたはEnterで次へ）">
          <div class="terms-kicker">注意事項</div>
          <h1 class="terms-title">3ステップで確認しよう！</h1>

          <div class="terms-panel">
            <div class="terms-badge" aria-hidden="true">1</div>
            <div class="terms-lead">対象と条件を確認する</div>
            {{-- ★修正: Copilot指摘に基づき文言を修正 --}}
            <p class="terms-desc">
                利用時間は原則として平日 9:10〜21:30 の授業時間内に限ります（実際の予約可能時間はシステム上の表示・制限に従ってください）。
                学生同士のみの使用は不可（教員同伴必須）。PASS証提示や申請書提出を事前に行う必要あり。
            </p>
          </div>

          <div class="terms-dots" aria-label="手順ナビ（ドット）">
            <button type="button" class="terms-dot" data-stepjump="1" data-active="true" aria-label="ステップ1へ"></button>
            <button type="button" class="terms-dot" data-stepjump="2" aria-label="ステップ2へ"></button>
            <button type="button" class="terms-dot" data-stepjump="3" aria-label="ステップ3へ"></button>
          </div>
        </article>

        {{-- STEP 2 --}}
        <article class="terms-card" data-step="2" tabindex="0" role="button" aria-label="ステップ2：申請書記入と提出（クリックまたはEnterで次へ）">
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

          <div class="terms-dots" aria-label="手順ナビ（ドット）">
            <button type="button" class="terms-dot" data-stepjump="1" aria-label="ステップ1へ"></button>
            <button type="button" class="terms-dot" data-stepjump="2" data-active="true" aria-label="ステップ2へ"></button>
            <button type="button" class="terms-dot" data-stepjump="3" aria-label="ステップ3へ"></button>
          </div>
        </article>

        {{-- STEP 3 --}}
        <article class="terms-card" data-step="3" tabindex="0" role="button" aria-label="ステップ3：利用日当日の対応（クリックまたはEnterで次へ）">
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

          <div class="terms-dots" aria-label="手順ナビ（ドット）">
            <button type="button" class="terms-dot" data-stepjump="1" aria-label="ステップ1へ"></button>
            <button type="button" class="terms-dot" data-stepjump="2" aria-label="ステップ2へ"></button>
            <button type="button" class="terms-dot" data-stepjump="3" data-active="true" aria-label="ステップ3へ"></button>
          </div>
        </article>

        {{-- 同意UI：/precautions のときだけ --}}
        @if($showAgree && $next)
          {{-- ★修正: IDとaria-hiddenを追加 --}}
          <div class="agree-wrap" id="agreeSection" aria-hidden="true">
            <form method="POST" action="{{ route('user.precautions.agree') }}" class="agree-form">
              @csrf
              <input type="hidden" name="next" value="{{ $next }}">

              <label class="skip-check">
                <input type="checkbox" name="skip_next_time" value="1">
                次回からこの注意事項を表示しない
              </label>

              {{-- ★修正: aria-disabled="true" を削除（JSで制御するため） --}}
              <button
                type="submit"
                class="agree-btn"
                id="agreeBtn"
                disabled
              >
                同意して予約へ進む
              </button>
            </form>
          </div>
        @endif

      </main>
    </div>
  </div>

  <script src="{{ asset('user/precautions.js') }}"></script>
</body>
</html>