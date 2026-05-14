@php
    /*
        注意事項ページの表示制御用変数

        - $showAgree : 同意フォームを表示するかどうか
        - $next      : 同意後に遷移するURL
        - $startStep : 初期表示するステップ番号
        - $mode      : 注意事項確認のみ / 同意あり の表示モード
    */
    $showAgree = (bool)($showAgree ?? false);
    $next = $next ?? null;
    $startStep = (int)($startStep ?? 1);
    $mode = $showAgree ? 'confirm' : 'terms';

    // 想定外のステップ番号が渡された場合は、STEP1から表示する
    if ($startStep < 1 || $startStep > 3) {
        $startStep = 1;
    }
@endphp

<!doctype html>
<html lang="ja">

<head>
    {{-- =========================================================
        基本設定・外部ファイル読み込み
        - 文字コード / レスポンシブ設定
        - 共通CSS
        - 注意事項ページ専用CSS
        - Tailwind CSS
        - Ionicons
    ========================================================= --}}
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>注意事項｜3ステップ案内</title>

    {{-- 共通CSS：サイドバーや全体レイアウトの土台 --}}
    <link rel="stylesheet" href="{{ asset('user/home.css') }}?v=999">
    <link rel="stylesheet" href="{{ asset('user/home.mobile.css') }}?v=999">

    {{-- 注意事項ページ専用CSS：カード・矢印・同意UIなどの装飾 --}}
    <link rel="stylesheet" href="{{ asset('user/precautions.css') }}?v=999">
    <link rel="stylesheet" href="{{ asset('user/precautions.mobile.css') }}?v=999">

    {{-- ユーティリティCSS --}}
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">

    {{-- アイコンライブラリ --}}
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>

<body data-start-step="{{ $startStep }}" data-mode="{{ $mode }}">
    {{-- =========================================================
        画面全体
        - data-start-step は JS 側で初期表示ステップを判定するために使用
        - data-mode は JS / CSS 側で通常表示か同意モードかを判定するために使用
    ========================================================= --}}

    {{-- スマホメニュー表示時の背景オーバーレイ --}}
    <div id="mobileOverlay" class="m-backdrop"></div>

    <div class="layout">
        {{-- 共通サイドバー --}}
        <x-user.sidebar />

        {{-- =====================================================
            メインコンテンツエリア
            - content-area は home.css 側の共通レイアウトで管理
            - terms-viewport は precautions.js のスワイプ判定対象
        ===================================================== --}}
        <div class="content-area">
            <main class="terms-viewport">

                {{-- -------------------------------------------------
                    注意事項カード全体
                    - STEP1〜STEP3 のカードを切り替え表示する
                    - スマホでは丸矢印ボタンで前後移動
                    - PCではカード左右のクリックエリアで前後移動
                    - data-step / data-active は JS 側のステップ制御で使用
                ------------------------------------------------- --}}
                <div class="terms-card-wrap">

                    {{-- スマホ用：前のステップへ戻る丸矢印 --}}
                    <button
                        type="button"
                        class="terms-arrow terms-arrow--prev"
                        id="arrowPrev"
                        aria-label="前のステップへ"
                    >
                        <ion-icon name="chevron-back-outline"></ion-icon>
                    </button>

                    {{-- PC用：カード左側クリックで前へ --}}
                    <button
                        type="button"
                        class="terms-click-zone terms-click-zone--prev"
                        id="pcZonePrev"
                        aria-label="前のステップへ"
                    >
                        <span class="terms-click-zone__icon" aria-hidden="true">
                            <ion-icon name="chevron-back-outline"></ion-icon>
                        </span>
                    </button>

                    {{-- PC用：カード右側クリックで次へ --}}
                    <button
                        type="button"
                        class="terms-click-zone terms-click-zone--next"
                        id="pcZoneNext"
                        aria-label="次のステップへ"
                    >
                        <span class="terms-click-zone__icon" aria-hidden="true">
                            <ion-icon name="chevron-forward-outline"></ion-icon>
                        </span>
                    </button>

                    {{-- =================================================
                        STEP 1：空き時間を確認・選択
                    ================================================= --}}
                    <article
                        class="terms-card"
                        data-step="1"
                        data-active="true"
                        aria-label="ステップ1: 空き時間を確認・選択する"
                    >
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

                        {{-- ステップ移動用ドット --}}
                        <div class="terms-dots">
                            <button
                                type="button"
                                class="terms-dot"
                                data-stepjump="1"
                                data-active="true"
                                aria-label="ステップ1へ"
                            ></button>

                            <button
                                type="button"
                                class="terms-dot"
                                data-stepjump="2"
                                aria-label="ステップ2へ"
                            ></button>

                            <button
                                type="button"
                                class="terms-dot"
                                data-stepjump="3"
                                aria-label="ステップ3へ"
                            ></button>
                        </div>
                    </article>

                    {{-- =================================================
                        STEP 2：申請フォームを入力・送信
                    ================================================= --}}
                    <article
                        class="terms-card"
                        data-step="2"
                        aria-label="ステップ2: 申請フォームを入力して送信する"
                    >
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

                        {{-- ステップ移動用ドット --}}
                        <div class="terms-dots">
                            <button
                                type="button"
                                class="terms-dot"
                                data-stepjump="1"
                                aria-label="ステップ1へ"
                            ></button>

                            <button
                                type="button"
                                class="terms-dot"
                                data-stepjump="2"
                                data-active="true"
                                aria-label="ステップ2へ"
                            ></button>

                            <button
                                type="button"
                                class="terms-dot"
                                data-stepjump="3"
                                aria-label="ステップ3へ"
                            ></button>
                        </div>
                    </article>

                    {{-- =================================================
                        STEP 3：当日の受け取り・返却
                    ================================================= --}}
                    <article
                        class="terms-card"
                        data-step="3"
                        aria-label="ステップ3: 当日・受け取りと返却"
                    >
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

                        {{-- ステップ移動用ドット --}}
                        <div class="terms-dots">
                            <button
                                type="button"
                                class="terms-dot"
                                data-stepjump="1"
                                aria-label="ステップ1へ"
                            ></button>

                            <button
                                type="button"
                                class="terms-dot"
                                data-stepjump="2"
                                aria-label="ステップ2へ"
                            ></button>

                            <button
                                type="button"
                                class="terms-dot"
                                data-stepjump="3"
                                data-active="true"
                                aria-label="ステップ3へ"
                            ></button>
                        </div>
                    </article>

                    {{-- スマホ用：次のステップへ進む丸矢印 --}}
                    <button
                        type="button"
                        class="terms-arrow terms-arrow--next"
                        id="arrowNext"
                        aria-label="次のステップへ"
                    >
                        <ion-icon name="chevron-forward-outline"></ion-icon>
                    </button>
                </div>

                {{-- -------------------------------------------------
                    同意フォーム
                    - $showAgree が true かつ $next が存在する場合のみ表示
                    - 注意事項確認後、同意して予約画面へ進む
                    - skip_next_time にチェックすると次回以降の注意事項表示を省略
                    - agreeBtn は JS 側で有効化される想定
                ------------------------------------------------- --}}
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

    {{-- 共通JS：サイドバーや共通UIの操作 --}}
    <script src="{{ asset('user/home.js') }}?v=999"></script>

    {{-- 注意事項ページ専用JS：ステップ切り替え・同意ボタン制御 --}}
    <script src="{{ asset('user/precautions.js') }}?v=999"></script>
</body>

</html>