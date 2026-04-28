<!doctype html>
<html lang="ja">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>夜間注意事項｜3ステップ案内</title>
    <link rel="stylesheet" href="{{ asset('user/night-precautions.css') }}">
</head>

<body>
    <main class="viewport">
        <!-- STEP 1 -->
        <article class="card" data-step="1" data-active="true">
            <div class="card-kicker">夜間注意事項</div>
            <h1 class="card-title">3ステップで確認しよう！</h1>

            <div class="panel">
                <button class="badge" data-stepjump="1" aria-label="ステップ1へ" tabindex="-1">1</button>
                <div class="lead">対象と条件を確認する</div>
                <p class="desc">
                    利用可能時間：金曜日 19:40 ～ 翌日 6:00
                    学年ごとの利用は不可（教員の許可が必須）
                    使用する全員分の氏名を事前に記入すること
                    AM6:00には完全退出すること
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
            <div class="card-kicker">夜間注意事項</div>
            <h1 class="card-title">3ステップで確認しよう！</h1>

            <div class="panel">
                <button class="badge" data-stepjump="2" aria-label="ステップ2へ" tabindex="-1">2</button>
                <div class="lead">事前準備と申請提出</div>
                <p class="desc">
                    使用教室の予約申請書を事前に提出すること
                    使用目的と終業を明確に記入
                    教員サイン（捺印）必須（ない場合は夜間利用不可）
                    使用する教室に〇をつける
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
            <div class="card-kicker">夜間注意事項</div>
            <h1 class="card-title">3ステップで確認しよう！</h1>

            <div class="panel">
                <button class="badge" data-stepjump="3" aria-label="ステップ3へ" tabindex="-1">3</button>
                <div class="lead">利用当日のルール</div>
                <p class="desc">
                    利用当日は必ずPASS証を携帯
                    使用教室以外への立ち入りは禁止
                    宿泊・仮眠は禁止（警備上の理由により）
                    利用後は速やかに退出・清掃・報告。
                </p>
            </div>

            <div class="dots" aria-label="手順ナビ（ドット）">
                <button class="dot" data-stepjump="1" aria-label="ステップ1へ"></button>
                <button class="dot" data-stepjump="2" aria-label="ステップ2へ"></button>
                <button class="dot" data-stepjump="3" data-active="true" aria-label="ステップ3へ"></button>
            </div>
        </article>
    </main>

    <script src="{{ asset('user/night-precautions.js') }}"></script>
</body>

</html>
