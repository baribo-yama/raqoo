<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>教室管理申請書</title>
    <link rel="stylesheet" href="reservation.css">
</head>
<body>
    <div class="container">
        <div class="form-header">
            <div class="form-title">教室管理申請書</div>
            <div class="form-subtitle">(使用申請書)</div>
        </div>
        <form class="request-form">
            <div class="row">
                <div class="form-group">
                    <label>代表者氏名</label>
                    <input type="text" />
                </div>
                <div class="form-group">
                    <label>学年</label>
                    <input type="text" />
                </div>
                <div class="form-group">
                    <label>コース名</label>
                    <input type="text" />
                </div>
            </div>
            <div class="form-group">
                <label>使用目的</label>
                <textarea placeholder="詳しく入力してください"></textarea>
            </div>
            <div class="row">
                <div class="form-group">
                    <label>日付</label>
                    <input type="date" />
                </div>
                <div class="form-group period-group">
                    <label>何限～何限使用するか</label>
                    <div class="period-inputs">
                        <span>限 ( </span>
                        <input type="text" class="period-input" maxlength="2" />
                        <span> ～ </span>
                        <input type="text" class="period-input" maxlength="2" />
                        <span> )</span>
                    </div>
                </div>
            </div>
  <div class="form-group">
  <label>使用メンバー</label>
  <div id="member-container" class="member-grid">
    <!-- 初期6人（削除ボタンなし） -->
    <input type="text" name="member[]" class="member-input" />
    <input type="text" name="member[]" class="member-input" />
    <input type="text" name="member[]" class="member-input" />
    <input type="text" name="member[]" class="member-input" />
    <input type="text" name="member[]" class="member-input" />
    <input type="text" name="member[]" class="member-input" />
  </div>
  <button type="button" class="add-member-btn" onclick="addMember()">＋ メンバーを追加</button>
</div>



        </form>
    </div>
    <script src="reservation.js"></script>
</body>
</html>
