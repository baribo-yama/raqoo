<!DOCTYPE html>
<html lang="ja">

<head>
  {{-- =========================================================
        基本設定・外部ファイル読み込み
        - 文字コード / レスポンシブ設定
        - 共通CSS
        - スマホ用共通CSS
        - Tailwind CSS
        - ユーザー情報画面専用CSS
        - Ionicons
    ========================================================= --}}
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ユーザー情報 - Raqoo</title>

  {{-- 共通CSS --}}
  <link rel="stylesheet" href="{{ asset('user/home.css') }}?v={{ time() }}">
  <link rel="stylesheet" href="{{ asset('user/home.mobile.css') }}?v={{ time() }}">

  {{-- ユーティリティCSS --}}
  <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">

  {{-- ユーザー情報画面専用CSS --}}
  <link rel="stylesheet" href="{{ asset('user/user-information.css') }}?v={{ time() }}">

  {{-- アイコンライブラリ --}}
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</head>

<body class="user-information-page">
  @php
  /*
  * ユーザー情報画面で使う表示用データをまとめる。
  * 履歴データが渡されていない場合でも画面が壊れないようにする。
  */
  $user = Auth::user();
  $borrowedRoomHistories = $borrowedRoomHistories ?? collect();
  @endphp

  {{-- =========================================================
        全体レイアウト
        - 左側に共通サイドバー
        - 右側にユーザー情報
    ========================================================= --}}
  <div class="layout">
    {{-- 共通サイドバー --}}
    <x-user.sidebar />

    {{-- スマホ用バックドロップ --}}
    <div class="m-backdrop" id="navBackdrop" aria-hidden="true"></div>

    {{-- =====================================================
            メインコンテンツエリア
        ===================================================== --}}
    <div class="content-area">
      <main class="ui-main">
        {{-- -------------------------------------------------
                    ページタイトル
                ------------------------------------------------- --}}
        <div class="page-title-area">
          <h1 class="page-title">ユーザー情報</h1>
        </div>

        <section class="ui-grid">
          {{-- =================================================
                        基本情報
                        - アカウント名
                        - 所属学校
                        - 学校メールアドレス
                    ================================================= --}}
          <article class="ui-panel">
            <header class="ui-panel-head">
              <h2>基本情報</h2>

              <button class="ui-edit-btn" type="button" aria-label="基本情報を編集">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 20h9" />
                  <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z" />
                </svg>

                <span>編集</span>
              </button>
            </header>

            <div class="ui-panel-body">
              <dl class="ui-kv">
                <div class="ui-kv-row">
                  <dt>アカウント名</dt>
                  <dd>{{ $user->name ?? '未設定' }}</dd>
                </div>

                <div class="ui-kv-row">
                  <dt>学籍番号</dt>
                  <dd>{{ $user->student_id ?? '未設定' }}</dd>
                </div>

                <div class="ui-kv-row">
                  <dt>所属学校</dt>
                  <dd>{{ $user->school_name ?? '未設定' }}</dd>
                </div>

                <div class="ui-kv-row">
                  <dt>学校のメールアドレス</dt>
                  <dd>{{ $user->email ?? '未設定' }}</dd>
                </div>
              </dl>
            </div>
          </article>

          {{-- =================================================
                        借りた教室履歴
                        - 履歴がある場合は表で表示
                        - 履歴がない場合は空状態を表示
                    ================================================= --}}
          <article class="ui-panel history-panel">
            <header class="ui-panel-head">
              <h2>借りた教室履歴</h2>
            </header>

            <div class="ui-panel-body">
              <div class="ui-table-wrap">
                @if ($borrowedRoomHistories->count() > 0)
                <table class="ui-table">
                  <thead>
                    <tr>
                      <th>借りた日</th>
                      <th>教室</th>
                      <th>時間帯</th>
                    </tr>
                  </thead>

                  <tbody>
                    @foreach ($borrowedRoomHistories as $history)
                    <tr>
                      <td>
                        {{ \Carbon\Carbon::parse($history->date)->format('Y-m-d') }}
                      </td>

                      <td>
                        {{ optional($history->room)->name ?? '教室情報なし' }}
                      </td>

                      <td>
                        {{ $history->period ?? '-' }}限
                      </td>
                    </tr>
                    @endforeach
                  </tbody>
                </table>
                @else
                <div class="ui-empty-state">
                  借りた教室履歴はまだありません。
                </div>
                @endif
              </div>
            </div>
          </article>
        </section>
      </main>
    </div>
  </div>

  {{-- 共通サイドバー開閉用JavaScript --}}
  <script src="{{ asset('user/home.js') }}?v={{ time() }}"></script>
</body>

</html>