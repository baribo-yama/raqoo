# Raqoo システム仕様書（ソースコードからの逆引き）

2026-10-07時点

> 本書は 2026-10-07 時点の `feature/mail-notification` ブランチのソースコードから読み取れる機能・仕様・要件をまとめたものです。
> 「〜とする」は実装されている挙動、「⚠️」は実装上の不整合・未実装・注意点を示します。
> 導入手順・テストアカウントは [README.md](README.md)、DB の初期構築は [database-setup.md](database-setup.md)、
> ステータス表示・セルモーダルの設計意図は [shiyousho.md](shiyousho.md) を参照してください。

---

## 1. システム概要

| 項目 | 内容 |
| --- | --- |
| 名称 | Raqoo（教室予約管理システム） |
| 目的 | 紙・窓口で行っていた教室予約を Web 化し、学生はどこからでも空き状況確認・予約申請を、管理者はオンラインで承認・授業管理を行えるようにする |
| 利用者 | 学生・教員（以下「ユーザー」）、教室管理者（以下「管理者」） |
| 構成 | Laravel 12 (PHP 8.3) / MariaDB / Nginx / Docker Compose、画面は Blade + 素の JS（`public/user/*.js`, `public/admin/*.js`）+ 一部 Tailwind |
| 外部連携 | Google OAuth（Laravel Socialite）、SMTP メール送信 |

### 1.1 アクター別の主な機能

| アクター | 機能 |
| --- | --- |
| ユーザー | Google ログイン / 初回プロフィール登録 / 教室の空き状況閲覧・検索 / 注意事項の確認・同意 / 予約申請（最大2コマ） / 予約一覧・詳細・キャンセル / 予約履歴 / お知らせ閲覧 / 利用規約・夜間注意事項の閲覧 |
| 管理者 | ID・パスワードログイン / ダッシュボード（日付別の教室×時限テーブル） / 予約申請の承認・却下（一括・単体） / 授業の登録・キャンセル / 承認済み予約のキャンセル / 教室一覧の閲覧 |

---

## 2. 基本マスタ・定数

### 2.1 時限（[config/period.php](../raqoo/config/period.php)）

| 時限 | 開始 | 終了 |
| --- | --- | --- |
| 1限 | 9:10 | 10:40 |
| 2限 | 10:50 | 12:20 |
| 3限 | 13:00 | 14:30 |
| 4限 | 14:40 | 16:10 |
| 5限 | 16:20 | 17:50 |
| 6限 | 18:00 | 19:30 |

⚠️ 同じ時間割が `Admin/DashBoardController` と `public/user/home.js` にもハードコードされている。

### 2.2 教室（初期データ: [RoomSeeder](../raqoo/database/seeders/RoomSeeder.php)）

- 1〜5階に各2部屋、計10教室（`101教室`〜`502教室`）
- `x01`: 定員30名・プロジェクター, ホワイトボード / `x02`: 定員25名・ホワイトボード
- 教室の追加・編集画面はない（管理者の「教室管理」は一覧表示のみ）

### 2.3 登録可能なメールドメイン

`@edu.sba.ac.jp` / `@edu.ssm.ac.jp` / `@sba.ac.jp` / `@ssm.ac.jp` のみ（Google ログイン時とプロフィール登録時の両方でチェック）。

---

## 3. 認証・アカウント

### 3.1 ユーザー認証

| 方式 | 内容 |
| --- | --- |
| Google ログイン | `/auth/google/redirect` → Google → `/auth/google/callback`。許可ドメイン以外は `/login` にエラー付きで戻す。`google_id` で `users` を検索し、無ければ作成（パスワードは `null`）してログイン |
| メール＋パスワード | `POST /login`（`Auth::attempt`）。Google 経由のユーザーはパスワードを持たないため、実質シーダーで作成したテストユーザー用 |
| ログアウト | `POST /logout`。セッション破棄・CSRF トークン再生成後 `/login` へ |

**初回プロフィール登録（`/signup`）**

- Google ログイン後、`student_id` が未設定なら `/signup` へリダイレクト
- 入力: 姓・名（必須, 255字以内）、学籍番号（必須, 一意）。メールは Google アカウントのもの（読み取り専用）
- 保存後 `/home` へ

⚠️ 注意点
- `/signup` ルートに `auth` ミドルウェアが付いていないため、未ログインで POST すると `$request->user()` が null になりエラーになる。
- `User` の `MustVerifyEmail` は無効化されており、メールアドレス確認は行っていない（README の「確認メール」の記述とは異なる）。
- 専攻（`major_id`）は DB にカラムがあるが入力は未実装。
- Breeze 由来の `routes/auth.php`（`/register`, `/forgot-password` 等）が残存している。`/login` は `web.php` の定義で上書きされる。

### 3.2 管理者認証

- `admins` テーブル／`admin` ガード（セッション）で、ユーザーとは完全に別系統
- `GET/POST /admin/login`（メール形式・必須チェック、"remember" 対応）、`POST /admin/logout`
- 管理者アカウントの作成画面はなく、シーダーで作成する

---

## 4. 利用可能時間（予約申請受付時間）

[TimeGuardMiddleware](../raqoo/app/Http/Middleware/TimeGuardMiddleware.php)（`time.guard`）により、以下の時間外は `/unavailable`（予約申請不可画面）へリダイレクトする。

| 曜日 | 受付時間 |
| --- | --- |
| 平日（月〜金） | 9:10〜19:30（両端含む） |
| 土日 | 10:50〜17:50（両端含む） |

- 対象ルート: `/home`、`/reservations/create`、`/reservations/check`、`POST /reservations`
- 予約一覧・履歴・お知らせ等は時間外でも閲覧可能
- 境界値は [TimeGuardMiddlewareTest](../raqoo/tests/Feature/TimeGuardMiddlewareTest.php) でテスト済み
- ⚠️ 画面文言にある「長期休暇期間は 10:50〜17:50」は未実装（祝日・休暇の判定なし）

**開発用**: `.env` の `APP_FAKE_NOW="2026-05-14 08:00:00"` で、production 以外では `now()` / `today()` を固定できる（`AppServiceProvider`）。

---

## 5. ユーザー機能

### 5.1 ホーム（教室一覧・空き状況） `GET /home`

- 日付タブ（1週間ストリップ、前後移動可）で日付を選び、教室×1〜6限の状態をカード表示
- 状態は `GET /user/room-status?date=` から取得（§7.2 参照）。`available` のカードのみ予約リンクが有効
- 検索フィルタ: 日付・開始〜終了時限・利用人数（定員以上）・階数（1〜5 以外の値は無視）
  - 開始 > 終了の場合は自動で入れ替え、info メッセージを表示
  - 日付が「今日+14日」を超えるとエラー
- スマホ（幅 560px 以下）では「自分の予約」シートを表示（`GET /user/my-reservations?date=`）。承認済み → 申請中 → その他の順に並ぶ

⚠️ 日付・時限による空き絞り込みは `room_statuses` テーブルを参照しているが、`room_statuses` は「承認時に `reserved` を書き込む」以外更新されない（ユーザー/管理者のキャンセル、授業登録で戻らない）。カード表示（`reservations` + `room_lessons` から算出）と検索結果が一致しないことがある。

### 5.2 注意事項・利用規約

| 画面 | 内容 |
| --- | --- |
| `GET /precautions?next=...` | 予約前の注意事項（3ステップ）。同意ボタンあり |
| `POST /precautions/agree` | 同意。「次回から表示しない」ON でセッションに `precautions_skip` を保存し、以後 `next` 付きアクセスはスキップ。`next` は `/` 始まりの内部パスのみ許可（`//` 始まり・絶対 URL は拒否＝オープンリダイレクト対策） |
| `GET /terms` | 利用規約（注意事項と同じ画面を同意 UI なしで表示） |
| `GET /night-precautions` | 夜間利用の注意事項（静的ページ） |

注意事項に記載されている運用ルール（システム外の運用）:
- 2名以上の利用では1教室につき2コマまで
- 承認・却下はアプリの「お知らせ」に届く
- 利用開始10分前に受付で手続きし鍵を受領。鍵受領時に全員分の学生証を提出し、返却時に返還
- 夜間利用: 金曜 19:40〜翌 6:00、教員のサイン必須、PASS 証携帯、宿泊・仮眠禁止 等（夜間予約機能自体はシステムにない）

⚠️ スキップ設定はセッション保存のため、ログアウトや期限切れでリセットされる。

### 5.3 予約申請フロー

```
ホームで空きカードを選択
  → /precautions（同意 or スキップ）
  → GET /reservations/create   予約フォーム
  → POST /reservations/check   確認画面
  → POST /reservations         保存（status = pending）
  → ホームへ「予約申請を送信しました。承認をお待ちください。」
```

**入力項目**

| 項目 | ルール |
| --- | --- |
| 教室・日付 | フォームで固定（hidden） |
| 時限 `periods[]` | 1〜2コマ、1〜6の整数、連続した時限のみ。選択元の時限は固定、予約済み・授業ありの時限は選択不可 |
| 利用目的 `purpose` | 必須・100字以内 |
| 利用メンバー `members` | 任意。代表者以外を最大30名（1名50字）、改行区切りで保存。初期5行表示、「5名追加」で拡張 |

**業務ルール（サーバー側チェック）**

| # | ルール | チェック箇所 |
| --- | --- | --- |
| R1 | 予約できるのは今日〜14日後まで | create（`check`/`store` は「今日以降」のみ） |
| R2 | 当日の、終了時刻を過ぎた時限は予約不可 | create / check / store |
| R3 | 今日に有効な自分の予約（pending/reserved/in_use）がある場合、その終了時刻までは今日の新規予約不可 | create |
| R4 | 明日以降は1日につき有効な予約1件まで（1回の申請で2コマ連続は可） | create |
| R5 | 2コマは連続した時限のみ | check / store |
| R6 | 対象枠に他人の有効予約・授業がある場合は不可 | check |
| R7 | 保存直前に `lockForUpdate` 付きで再チェック（キャンセル以外の予約・授業・同時刻に自分が別教室を予約）。衝突時はフォームへ戻し「タッチの差で…」等を表示 | store（トランザクション内） |
| R8 | 1名利用（メンバー未入力）の場合は連続予約不可 | **フロントのみ**（`reservation-form.js`） |

- 1コマ＝1レコードとして `reservations` に保存（2コマなら2レコード）。初期ステータスは `pending`
- ⚠️ R1 の14日上限は `check`/`store` では検証していないため、リクエストを直接送れば回避できる
- ⚠️ R8 はサーバーで検証していない

### 5.4 予約一覧・詳細・キャンセル・履歴

| 画面 / API | 内容 |
| --- | --- |
| `GET /reservations` | 今日以降の自分の予約（全ステータス）を日付・時限順で表示 |
| `GET /reservations/{id}` | 予約詳細。他人の予約は 403 |
| `PATCH /reservations/{id}/cancel` | 自分の `pending` / `reserved` の予約を `cancelled` に変更。他人の予約は 403 |
| `GET /reservations-history` | 過去日付、または `finished` / `cancelled` の予約を新しい順に10件ずつ |
| `GET /user/my-reservations` | 自分の予約を JSON で返す（`date` 指定、`limit` 1〜100・既定50） |

⚠️ ユーザーキャンセル時に管理者への通知はない。キャンセルの期限（何分前まで等）の制約もない。

### 5.5 お知らせ `GET /notifications`

- 自分宛ての通知を新しい順に10件ずつ表示
- 画面を開いた時点で未読をすべて既読にする
- サイドバーに未読件数バッジを表示（`View/Components/User/Sidebar`）

---

## 6. 管理者機能

### 6.1 ダッシュボード `GET /admin/dashboard`

**予約申請一覧（承認待ち）**

- `pending` の予約を、「同一ユーザー・同一日付・同一教室・同一目的・同一メンバー・連続時限」で1件にまとめて表示（例: `3~4限`）
- 各グループに「承認」「却下」ボタン → 一括承認／一括却下（却下は確認ダイアログあり）
- ヘッダーのバッジは `GET /admin/reservations/count`（同じグループ化ロジックでの件数）を **30秒ごと** にポーリング

**教室×時限テーブル**

- 日付を選択すると `GET /admin/room-status?date=` で全教室・全時限の状態を取得
  - 授業あり → 授業名をグレー表示
  - 承認済み予約（reserved/in_use）→「予約」を青表示
  - 申請中（pending）はテーブルには表示しない
- セルをクリックすると `GET /admin/room-cell-detail` で詳細を取得し、状態に応じたモーダルを表示

| セル状態 | モーダル | 操作 |
| --- | --- | --- |
| 授業あり | 授業名 | 授業キャンセル `DELETE /admin/room-lesson/cancel`（`room_lessons` から削除） |
| 予約あり | 代表者名・メンバー・ステータス | 予約キャンセル `POST /admin/reservations/cancel`（`reservations` から**物理削除**） |
| 空き | 授業名入力（必須, 100字以内） | 授業登録 `POST /admin/room-lesson`（同枠があれば上書き） |

- 授業は「教室・日付・時限」で一意（DB ユニーク制約）。授業登録された枠はユーザー側で `unavailable`（授業名表示）になり、予約不可
- ⚠️ 授業登録時、同じ枠の既存予約（pending/reserved）はそのまま残る（自動却下・通知なし）
- ⚠️ 管理者による予約キャンセルは物理削除で、ユーザーへの通知・メールはない。`room_statuses` も戻らない

### 6.2 予約管理 `GET /admin/reservations`

- `pending` の予約を1コマ単位で一覧表示し、単体の承認・却下ができる

### 6.3 承認・却下の処理内容

| 操作 | ステータス変更 | `room_statuses` | お知らせ | メール |
| --- | --- | --- | --- | --- |
| 一括承認（ダッシュボード） | pending → reserved | `reserved` を upsert | 「予約（全Nコマ）が承認されました。教室 - 日付 X〜Y限」 | なし |
| 一括却下（ダッシュボード） | pending → cancelled | 変更なし | 「予約（全Nコマ）が却下されました。…」 | なし |
| 単体承認（予約管理） | pending → reserved | `reserved` を upsert | 「予約が承認されました。…」 | `ReservationApproved` |
| 単体却下（予約管理） | pending → cancelled | 変更なし | 「予約が却下されました。…」 | `ReservationRejected` |

- いずれも `pending` 以外は処理しない（「既に処理されています」等のエラー）
- 一括処理はトランザクション内で実行
- 却下も DB 上は `cancelled` として保存される（`rejected` というステータスは無い）

⚠️ メール送信まわり（現ブランチの作業対象）
- `ReservationApproved` のテンプレート名が `emails.reservation-approveed`（誤字）になっており、実在するのは `emails/reservation-approved.blade.php`。単体承認時にビューが見つからず例外になる（DB 更新・お知らせ作成後に 500 エラー）
- 主導線であるダッシュボードの一括承認・却下ではメールが送られない
- メールは同期送信（`ShouldQueue` 未実装）。SMTP 障害時は承認処理自体がエラーになる
- 却下理由: `Reservation::$fillable` やユーザー側の画面に `rejection_reason` があるが、DB カラム・入力 UI は存在しない

### 6.4 その他

- `GET /admin/rooms`: 教室一覧（閲覧のみ）
- `POST /admin/room-status`: `room_statuses` を直接更新する API（現在フロントからは未使用）
- ⚠️ `GET /admin/notifications` は `admin.notifications-index` ビューを返すが、ビューが存在しない
- ⚠️ ユーザー側の `GET /info` も `user.info` ビューが存在しない（`user-information.blade.php` はある）

---

## 7. ステータス仕様

### 7.1 予約ステータス（`reservations.status`）

| 値 | 意味 | 遷移元 |
| --- | --- | --- |
| `pending` | 申請中 | 新規申請時 |
| `reserved` | 予約済み（承認済み） | 管理者の承認 |
| `in_use` | 利用中 | ⚠️ 遷移させる処理なし |
| `cancelled` | キャンセル／却下 | ユーザーのキャンセル、管理者の却下 |
| `finished` | 利用終了 | ⚠️ 遷移させる処理なし（スケジューラ未実装） |

```
           承認           (未実装)          (未実装)
pending ──────────▶ reserved ──────▶ in_use ──────▶ finished
   │                   │
   │ 却下／ユーザー取消 │ ユーザー取消
   ▼                   ▼
cancelled ◀────────────┘
（管理者のセルキャンセルは物理削除）
```

「有効な予約」は `pending` / `reserved` / `in_use` の3つとして扱われる。

### 7.2 ユーザー画面の枠ステータス（`GET /user/room-status`）

上から順に判定し、最初に該当したものを返す（[Room::getStatusForUser](../raqoo/app/Models/Room.php)）。

1. 授業あり → `unavailable`（`lesson_title` に授業名、画面は授業名を表示）
2. 自分の有効予約 → その予約のステータス（`pending` / `reserved` / `in_use`）
3. 他人の `reserved` / `in_use` → `unavailable`
4. 上記以外 → `available`

さらに以下で上書きする。

- `available` かつ同じ日・同じ時限に自分の有効予約が別教室にある → `conflict`（「同時刻に予約あり」）
- 対象日が今日で、その時限の終了時刻を過ぎている → `unavailable`

⚠️ 他人の `pending` は `available` のまま表示されるため、選択はできるが確認画面（R6）で弾かれる。

### 7.3 管理者画面の枠ステータス（`GET /admin/room-status`）

`lesson`（授業あり）＞ `reserved`（reserved/in_use の予約あり）＞ `available` の順。`pending` は表示しない。

---

## 8. データモデル

| テーブル | 主なカラム | 備考 |
| --- | --- | --- |
| `users` | family_name, first_name, student_id(12, unique), major_id(FK), email(unique), google_id(unique), password(nullable) | Google ログインで作成 |
| `admins` | email(unique), password | 管理者 |
| `rooms` | name(50, unique), floor, capacity, equipment | 教室マスタ |
| `reservations` | user_id(FK), room_id(FK), date, period(1-6), status(enum), purpose(100), members(text, 改行区切り) | 1コマ1レコード。`member_count` アクセサ＝代表者1＋メンバー行数 |
| `room_lessons` | room_id(FK), date, period, title(100) | (room_id, date, period) ユニーク |
| `room_statuses` | room_id(FK), date, period, status(available/reserved/in_use/unavailable) | 承認時のみ更新。ホームの検索絞り込みで使用 |
| `notifications` | user_id(FK), message, is_read | ユーザー向けお知らせ |
| `departments` / `majors` | name / name, department_id | 未使用（画面なし） |
| `sessions`, `cache`, `jobs` 等 | — | Laravel 標準 |

- `reservations` / `room_lessons` / `room_statuses` / `notifications` は親（user/room）削除時にカスケード削除
- ⚠️ `reservations` に (room_id, date, period) のユニーク制約はなく、重複防止はアプリ側のロック付きチェック（R7）に依存
- ⚠️ `RoomStatusSeeder` は `DatabaseSeeder` から呼ばれていない

---

## 9. ルート一覧

### 9.1 ユーザー（`auth` 必須。※は `time.guard` 付き）

| メソッド | パス | 内容 |
| --- | --- | --- |
| GET | `/home` ※ | ホーム（教室一覧） |
| GET | `/reservations/create` ※ | 予約フォーム |
| POST | `/reservations/check` ※ | 確認画面 |
| POST | `/reservations` ※ | 予約保存 |
| GET | `/reservations` | 予約一覧 |
| GET | `/reservations/{reservation}` | 予約詳細 |
| PATCH | `/reservations/{reservation}/cancel` | キャンセル |
| GET | `/reservations-history` | 予約履歴 |
| GET | `/notifications` | お知らせ |
| GET | `/terms` | 利用規約 |
| GET | `/precautions` / POST `/precautions/agree` | 注意事項・同意 |
| GET | `/night-precautions` | 夜間注意事項 |
| GET | `/unavailable` | 受付時間外画面 |
| GET | `/user/room-status` | 枠ステータス（JSON） |
| GET | `/user/my-reservations` | 自分の予約（JSON） |

認証不要: `GET/POST /login`, `POST /logout`, `GET/POST /signup`, `GET /auth/google/redirect`, `GET /auth/google/callback`

### 9.2 管理者（`auth:admin` 必須）

| メソッド | パス | 内容 |
| --- | --- | --- |
| GET | `/admin/dashboard` | ダッシュボード |
| GET | `/admin/reservations` | 承認待ち一覧 |
| GET | `/admin/reservations/count` | 承認待ち件数（JSON） |
| POST | `/admin/reservations/bulk-approve` / `bulk-reject` | 一括承認・却下（`ids`=カンマ区切り） |
| POST | `/admin/reservations/{id}/approve` / `reject` | 単体承認・却下 |
| POST | `/admin/reservations/cancel` | 予約削除（JSON） |
| GET | `/admin/room-status` | 枠ステータス（JSON） |
| POST | `/admin/room-status` | room_statuses 更新（JSON） |
| GET | `/admin/room-cell-detail` | セル詳細（JSON） |
| POST | `/admin/room-lesson` | 授業登録（JSON） |
| DELETE | `/admin/room-lesson/cancel` | 授業削除（JSON） |
| GET | `/admin/rooms` | 教室一覧 |

認証不要: `GET/POST /admin/login`, `POST /admin/logout`

---

## 10. 非機能要件・運用

| 区分 | 内容 |
| --- | --- |
| 動作環境 | Docker Compose（app: PHP-FPM, nginx: `127.0.0.1:8881`, db: MariaDB `127.0.0.1:8307`, TZ=Asia/Tokyo, utf8mb4） |
| 対応端末 | PC・スマートフォン（ホーム・お知らせ・注意事項にモバイル用 CSS、560px 以下でレイアウト切替） |
| セキュリティ | CSRF（Laravel 標準）、ユーザー/管理者のガード分離、他人の予約への 403、注意事項の `next` の内部パス制限、階数パラメータのホワイトリスト、同時申請時の行ロック |
| HTTPS | production または `FORCE_HTTPS=true` で HTTPS を強制。ローカルでは ngrok ホストのときのみ強制。全プロキシを信頼（`trustProxies(at: '*')`） |
| 同時実行 | 予約保存はトランザクション＋`lockForUpdate`。承認の一括処理もトランザクション |
| リアルタイム性 | 管理者の承認待ちバッジは30秒ポーリング。ユーザー画面は日付切替・画面表示時に取得（自動更新なし） |
| テスト | `TimeGuardMiddlewareTest`（受付時間の境界値）、他は Breeze 標準テストのみ |

既知のセキュリティ課題は [security-vulnerabilities.md](security-vulnerabilities.md) を参照。

---

## 11. ソースから読み取れる未実装・不整合の一覧

| # | 内容 | 該当箇所 |
| --- | --- | --- |
| 1 | 承認メールのテンプレート名の誤字で単体承認がエラーになる | `app/Mail/ReservationApproved.php` |
| 2 | 一括承認・却下でメールが送られない | `Admin/ReservationController::bulkApprove/bulkReject` |
| 3 | 却下理由のカラム・入力 UI がない（画面側は表示枠あり） | `Reservation::$fillable`, `reservation-detail`, `reservations-history` |
| 4 | `in_use` / `finished` への自動遷移がない | — |
| 5 | `room_statuses` がキャンセル・授業登録で更新されず、ホームの検索結果とカード表示がずれる | `User/RoomController::index` |
| 6 | 管理者の予約キャンセルが物理削除で、ユーザーに通知されない | `Admin/RoomStatusController::cancelReservation` |
| 7 | 授業登録時に同枠の既存予約が残る | `Admin/RoomStatusController::storeLesson` |
| 8 | 14日上限・1名時の連続予約不可がサーバー側の保存処理で検証されていない | `User/ReservationController::check/store` |
| 9 | `/signup` に `auth` ミドルウェアがない | `routes/web.php` |
| 10 | 存在しないビューを参照するルート（`/admin/notifications`, `/info`） | `routes/web.php` |
| 11 | 長期休暇期間の受付時間が未実装 | `TimeGuardMiddleware` |
| 12 | `check()` で存在しない `Auth::user()->name` を参照（確認画面は別項目を使うため表示に影響なし） | `User/ReservationController::check` |
| 13 | 時間割が3箇所に重複定義 | `config/period.php`, `DashBoardController`, `home.js` |
