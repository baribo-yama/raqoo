<!DOCTYPE html>
<html lang="ja">

<head>
    <!-- =========================================================
        基本設定・外部ファイル読み込み
        - 文字コード / レスポンシブ設定
        - 予約申請書画面専用CSS
    ========================================================= -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>教室管理申請書</title>

    <!-- 予約申請書画面専用CSS -->
    <link rel="stylesheet" href="reservation.css">
</head>

<body>
    <!-- =========================================================
        画面全体コンテナ
        - 教室管理申請書の入力フォーム全体を囲む
    ========================================================= -->
    <div class="container">

        <!-- -------------------------------------------------
            申請書ヘッダー
            - タイトル
            - サブタイトル
        ------------------------------------------------- -->
        <div class="form-header">
            <div class="form-title">教室管理申請書</div>
            <div class="form-subtitle">(使用申請書)</div>
        </div>

        <!-- -------------------------------------------------
            教室使用申請フォーム
            - 代表者情報
            - 使用目的
            - 使用日 / 使用時限
            - 使用メンバー
        ------------------------------------------------- -->
        <form class="request-form">

            <!-- =================================================
                代表者情報
                - 代表者氏名
                - 学年
                - コース名
            ================================================= -->
            <div class="row">
                <div class="form-group">
                    <label for="representative-name">代表者氏名</label>
                    <input type="text" id="representative-name" name="representative_name">
                </div>

                <div class="form-group">
                    <label for="grade">学年</label>
                    <input type="text" id="grade" name="grade">
                </div>

                <div class="form-group">
                    <label for="course-name">コース名</label>
                    <input type="text" id="course-name" name="course_name">
                </div>
            </div>

            <!-- =================================================
                使用目的
                - 教室を何のために使用するか入力
            ================================================= -->
            <div class="form-group">
                <label for="purpose">使用目的</label>
                <textarea id="purpose" name="purpose" placeholder="詳しく入力してください"></textarea>
            </div>

            <!-- =================================================
                使用日時
                - 使用日
                - 使用する時限の範囲
            ================================================= -->
            <div class="row">
                <div class="form-group">
                    <label for="use-date">日付</label>
                    <input type="date" id="use-date" name="use_date">
                </div>

                <div class="form-group period-group">
                    <label>何限～何限使用するか</label>

                    <div class="period-inputs">
                        <span>限 ( </span>

                        <input
                            type="text"
                            name="start_period"
                            class="period-input"
                            maxlength="2"
                            aria-label="開始時限"
                        >

                        <span> ～ </span>

                        <input
                            type="text"
                            name="end_period"
                            class="period-input"
                            maxlength="2"
                            aria-label="終了時限"
                        >

                        <span> )</span>
                    </div>
                </div>
            </div>

            <!-- =================================================
                使用メンバー
                - 初期表示は6人分の入力欄
                - 追加ボタンで reservation.js 側の処理を実行
            ================================================= -->
            <div class="form-group">
                <label>使用メンバー</label>

                <div id="member-container" class="member-grid">
                    <!-- 初期6人分の入力欄 -->
                    <input type="text" name="member[]" class="member-input" aria-label="使用メンバー1人目">
                    <input type="text" name="member[]" class="member-input" aria-label="使用メンバー2人目">
                    <input type="text" name="member[]" class="member-input" aria-label="使用メンバー3人目">
                    <input type="text" name="member[]" class="member-input" aria-label="使用メンバー4人目">
                    <input type="text" name="member[]" class="member-input" aria-label="使用メンバー5人目">
                    <input type="text" name="member[]" class="member-input" aria-label="使用メンバー6人目">
                </div>

                <button type="button" class="add-member-btn" id="addMemberButton">
                    ＋ メンバーを追加
                </button>
            </div>
        </form>
    </div>

    <!-- 予約申請書画面専用JavaScript -->
    <script src="reservation.js"></script>
</body>

</html>