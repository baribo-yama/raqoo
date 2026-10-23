# 修正すべき課題一覧

調査日：2026-10-23
対象ブランチ：refactor/rearchitecture（develop と同一コミット 007a435 時点）
調査範囲：リポジトリ全体（コードレビュー、技術改善点の洗い出し）

本ドキュメントは「今回の調査で見つかった、修正すべき課題」を重複なく整理したものです。後日、優先度付けとタスク化を行う前提の一覧であり、実装方針の合意はまだ取れていません。

DDD 化の方針そのものは [`rearchitecture-plan.md`](./rearchitecture-plan.md) を参照してください。そちらで扱っている項目（enum 化、ReservationPolicy 抽出、RoomSchedule 統合、UseCase 層、ドメインイベント）は、本書では「関連」として番号のみ示し、再掲しません。

## 凡例

- 優先度：**高**＝バグ・データ不整合 / **中**＝セキュリティ・設計 / **低**＝保守性・運用
- 関連：rearchitecture-plan.md の移行ステップ番号

---

## 優先度：高（バグ・データ整合性）

### H-1. 予約の二重取りを DB が防げていない

- `reservations` に `(room_id, date, period)` のユニーク制約がない。`room_lessons` にはある。
- 重複防止が `User/ReservationController::store()` の `lockForUpdate()->exists()` のみ。該当行が 0 件のときはロック対象がなく、MySQL/MariaDB の REPEATABLE READ ではギャップロックによるデッドロックや、すり抜けが起こり得る。
- 対策案：アクティブな予約（pending / reserved / in_use）だけに効くユニーク制約（MariaDB は生成列＋unique。`NULL` は重複扱いされない）、または教室×日付の行ロック。

### H-2. `rejection_reason` と `'rejected'` が未実装

- [Reservation.php](../../raqoo/app/Models/Reservation.php) の `$fillable`、`reservation-detail.blade.php`、`reservations-history.blade.php` が参照している。
- マイグレーションに `rejection_reason` 列がなく、`status` の enum に `rejected` もない。却下理由の表示ブロックは常に表示されない。
- 仕様として「却下とキャンセルを分けるか」が未決定（rearchitecture-plan.md の要検討事項と同じ論点）。決定後に列追加とステータス追加を行う。

### H-3. キャンセル・削除と `room_statuses` が矛盾する

- ユーザー側 `cancel()` は `RoomStatus` を更新しない。
- 管理者側 `Admin/RoomStatusController::cancelReservation` は予約を物理 DELETE する。履歴が消え、`RoomStatus` も残る。
- `Room::getStatusForPeriod` と `RoomStatus::getStatusForRoom` が別々の真実源から計算している。
- 対策案：物理削除をやめてステータス遷移にする。`room_statuses` は `reservations` と `room_lessons` から導出できるため、テーブルごとの廃止を検討（使用箇所の調査が先）。関連：ステップ 6。

### H-4. `User/ReservationController::create()` の当日予約ブロックが仕様と合っていない

- `$date` が今日で、今日の予約が 1 件でもあれば、他の時限を含めて新規予約ができなくなる。
- 実際の仕様（利用終了後に次を予約できる）に合わせ、判定は「その予約の period の終了時刻」を基準に組み直す。2 コマ予約（p_min / p_max）の扱いも合わせて確認する。関連：ステップ 2。

### H-5. 承認・却下の処理境界とメール送信が不統一

- `approve()` / `reject()` は `DB::transaction` で包まれていない（`bulkApprove` / `bulkReject` は包まれている）。
- `Mail::send` が同期で、try/catch もキューもない。SMTP 障害・`$reservation->user` が null / メール空の場合、DB 更新後に例外となり、管理者が再試行すると「承認待ちではありません」になる。遅い SMTP はリクエストをブロックする。
- 対策案：状態更新・`RoomStatus`・`Notification` を 1 トランザクションにし、メールは `afterCommit` のイベント＋キュー（`ShouldQueue`、`tries` / `backoff`、`failed_jobs` 監視）に出す。関連：ステップ 4・5。

### H-6. メール機能（PR #210）の不具合

- [ReservationApproved.php:41](../../raqoo/app/Mail/ReservationApproved.php#L41) のビュー名が `emails.reservation-approveed`（綴りミス）。承認時に `View not found` で 500 になる。
- `bulkApprove` / `bulkReject` はメールを送らない（単体承認・却下のみ送信）。
- ~~メール本文のボタンが `url('/reservations')` で管理者専用ルートを指している~~ → **誤検知として取り消し**。`/reservations` はユーザー用の `user.reservations.index` で、管理者用は `/admin/reservations`（2026-10-23 に routes/web.php で確認）。対応不要。

---

## 優先度：中（セキュリティ・設計）

### M-1. 認証・認可

- **ログインにレート制限がない**：`LoginController` と `Admin/LoginController` が Breeze の `LoginRequest`（throttle 付き）を使わず `Auth::attempt` を直接呼んでいる。`throttle` ミドルウェアか `LoginRequest` の再利用が必要。
- **`/signup` が認証なしで公開**：`Route::post('/signup')` が auth グループ外。`SignupController` は `$request->user()` を使うため、未認証だと null で 500。ドメイン検証もフォーム入力値で行っており、Google 認証済みのアドレスを見ていない。`auth` を付け、`$user->email` で検証する。
- **メール認証とドメイン制限が二重・不整合**：`User` の `MustVerifyEmail` がコメントアウトされているのに、`routes/auth.php` や `TimeGuardMiddlewareTest` が verified 前提。ドメイン許可リストが `GoogleLoginController` と `SignupController` にコピーされている。`config` に一本化し、小文字化して比較する。
- **Google OAuth**：`hd`（hosted domain）の指定と `email_verified` の確認がない。`firstOrCreate(['google_id' => ...])` は、同じメールの既存ユーザーがいると別アカウント作成を試みて `users.email` の unique 違反（500）になり得る。メールでも照合する。
- **`trustProxies(at: '*')`**：`X-Forwarded-*` を偽装でき、`forceScheme` や `getHost()` 判定に影響する。信頼する IP を実際のプロキシに限定する。
- **認可が分散**：所有者チェックが `user_id !== Auth::id()` の手書き（`show()` / `cancel()`）。Laravel の Policy（`$this->authorize()`）にまとめる。rearchitecture-plan.md の `ReservationPolicy`（ドメインサービス）と名前が衝突するため、命名を分ける。
- **管理者操作の権限境界**：`cancelReservation`（任意の予約を物理削除）と `updateStatus`（`room_statuses` を任意に書き換え）が `auth:admin` のみで保護。管理者アカウント侵害時の被害が大きいため、監査ログ（誰がいつ何をしたか）を追加する。

### M-2. 入力検証・設定の堅牢性

- `date` が `required|date` のみで範囲制限がない（`storeLesson` は過去日も登録可能）。
- `bulkApprove` / `bulkReject` の `ids` を `explode(',')` した値を整数検証なしで `whereIn` に渡している（バインドされるため SQL インジェクションではないが、`array|integer` で検証する）。
- [config/app.php:129](../../raqoo/config/app.php#L129)：`env('force_https, false')` とキーが誤っており、`FORCE_HTTPS` が読まれない。
- `APP_FAKE_NOW`：[AppServiceProvider.php](../../raqoo/app/Providers/AppServiceProvider.php) で `env()` を直接呼んでおり、`config:cache` 後は無効になる。非 production 環境に残ると全リクエストの時刻が固定される。PHP のデフォルトタイムゾーンでパースされる点も含め、`config()` 経由・テスト専用に寄せる。
- `TIME_GUARD_ENABLED` は `.env` の綴りミスが truthy として扱われ、開発での無効化が効かない場合がある（フェイルセーフ側なので軽微）。

### M-3. ドメインルールの散在

- 「14 日先まで」「1 日 1 予約」「2 コマまで」「`max:6`（時限数）」「営業時間 19:30 / 17:50」が各所にハードコード。`TimeGuardMiddleware` の時刻は `config/period.php` から導出できる。時限定義が変わると必ず食い違う。関連：ステップ 2。
- マジックストリング約 40 か所（`pending` / `reserved` / `in_use` / `cancelled`）。backed enum と「アクティブな予約」スコープに集約する。関連：ステップ 1。

### M-4. データモデル

- **2 コマ予約の表現**：1 申請が 2 行で、`Admin/ReservationController::count` と `DashBoardController` が `user_id`＋`date`＋`room_id`＋`purpose`＋`members`＋連続 period の一致で申請を推測している。同じ人が同じ目的で別々に取った予約が結合され得る。承認・通知も 1 件分のみ。`reservation_groups`（申請単位）と `reservations.group_id` を導入する案を推奨。
- **履歴の保全**：`reservations.user_id` / `room_id` が `onDelete('cascade')`。ユーザーや教室の削除で履歴が全消えする。`restrict` か `softDeletes` にする。承認・却下者と日時（`decided_by` / `decided_at`）も保持する。
- **ENUM 列**：`status` の MySQL ENUM は値追加が重い（`rejected` / `expired` など）。`string`＋アプリ側 enum、または `CHECK` 制約にする。
- **インデックス不足**：`reservations` に `(date, room_id, period)`、`(user_id, date, status)`、`(status, date)`。`notifications` に `(user_id, is_read)`。
- **`members`**：改行区切り `text` を `explode("\n")` で数えている。人数上限や二重参加チェックをするなら `reservation_members` テーブルが必要。
- **`period` の二重定義**：DB に `tinyInteger`、定義は `config/period.php`。時間割が学期で変わるならマスタ化を検討。
- **通知**：自作 `Notification` モデルが Laravel 標準の `Illuminate\Notifications\Notification` と名前衝突。標準の `Notifiable`（`database` / `mail` チャネル）への移行を検討。
- **マイグレーションの不整合**：`room_statuses` の `down()` が空。`users` が 2 回 create されている（`0001_01_01_000000` と `2025_05_29`）。`departments` / `majors` の日付体系が崩れている。`users.major_id` は未使用のため削除を検討。

---

## 優先度：低（保守性・運用）

### L-1. 重複・不要コード

- 重複ロジック：`Admin/RoomStatusController::getStatus` と `User/RoomStatusController::getStatus` の `lessonMap` / `reservationMap` 構築。`count()` と `DashBoardController` のグルーピング。関連：ステップ 3。
- `Log::info('updateStatus called', $request->all())`：リクエスト全体を本番ログに出力している。個人情報の漏洩源になり得るため削除する。
- 未使用・デバッグ：`PollTestController`（空）、`TestMail` と `testMailView`（さらに `TestMail` のビュー名 `mail.testMailView` は実在パスと不一致）、`User/RoomStatusController` の `$startTime`、コメントアウトされた `dd`。
- `updateStatus` API がフロントから使われているか確認し、不要なら削除する（H-3 と合わせて判断）。

### L-2. パフォーマンス・非機能要件

- **自動更新がない**：ユーザー画面の空き状況は、日付変更時のみ `fetch` で取得する（Ajax は導入済み）。管理者の承認待ちバッジだけ 30 秒ポーリング。まずユーザー画面にポーリング（15〜30 秒、`document.hidden` 中は停止、`ETag` または軽量な更新判定エンドポイント）を足す。Livewire への全面移行は推奨しない。即時性が必要になったら Reverb（WebSocket）を検討する。
- **フォーム送信時の競合**：他者に取られた場合を、リダイレクトではなく JSON（422＋最新の空き状況）で返し、再読み込みなしで再選択できるようにする。
- **キャッシュ未使用**：`Cache::` が 0 件。`Room::all()` や日付ごとの授業・予約を毎回取得し、6 時限分を PHP のループで計算している。`rooms` / `period` は長期キャッシュ、状況マップは 10〜30 秒のキャッシュ＋予約更新時の無効化。`admin.reservations.count` も全 pending を取得して PHP でグルーピングしている。
- **ドライバ不整合**：`SESSION_DRIVER` / `QUEUE_CONNECTION` が database、`CACHE_DRIVER=file`（`.env.example` は `CACHE_STORE=database`）。負荷増・複数コンテナ化を見据えて Redis に統一する。
- **ステータス遷移ジョブがない**：`in_use` / `finished` に遷移する処理がない。`routes/console.php` が空。スケジューラで毎分、時限開始で `in_use`、終了で `finished` に遷移させる。
- **観測性**：構造化ログ、エラー通知（Sentry など）、`/up` の監視。
- **タイムゾーン**：DB を UTC / JST どちらで保存するかを決めて文書化する。`docker-compose.yml` の `TZ` と PHP 設定が別々。
- **フロント資産**：`public/user/home.js` などを `asset()` で直読みし、Vite を通していない。`ionicons` を unpkg CDN から SRI なしで読み込んでいる。Vite に統合する。
- **アクセシビリティ・モバイル**：グリッドの `aria` 属性、色だけに頼らないステータス表示、`prefers-reduced-motion` への対応を確認する。

### L-3. セキュリティ（運用面）

- セッション・Cookie：`SESSION_ENCRYPT=false`。本番用の `SESSION_SECURE_COOKIE` / `SAME_SITE` を `.env.example` に明記する。
- レスポンスヘッダ：HSTS、CSP（外部 CDN を使うため特に）、`X-Frame-Options`、`Referrer-Policy` が `docker/nginx/default.conf` にない。
- 依存関係：`composer audit` / `npm audit` の定期実行。`composer.lock` がリポジトリ直下とアプリ配下の 2 つあり、どちらが正か不明（直下 `composer.json` の用途も不明）。
- `/user/room-status` が `lesson_title` を全ユーザーに返している。授業名の公開可否を確認する。
- 個人情報（学籍番号・氏名・メール、`members` に含まれる他人の氏名）の保持期間と削除・匿名化の方針を決める。

### L-4. テスト・CI・開発環境

- **テストがほぼない**：`tests/Feature` は Breeze 標準と `TimeGuardMiddlewareTest` のみ。リファクタリング前に、予約作成の重複・連続時限・14 日ルール・承認／却下の遷移・`Mail::fake()` によるメール送信（H-6 の綴りミスもこれで検知できる）を固定する。
- `.github/` に CI がなく、Pint / PHPUnit が自動で走らない。
- リポジトリ直下に `log/`（apt ログ）、`FETCH_HEAD`、`test/*.html` がコミットされている。削除と `.gitignore` 追加。
- `docker-compose.yml`：DB の root パスワードが `root` 固定、`mariadb:latest`。ローカル専用であることを README に明記するか `.env` 化し、タグを固定する。
- Docker が開発用（xdebug 入り）のみ。本番用 Dockerfile（opcache 有効・xdebug なし・非 root）と、`config:cache` / `route:cache` / `view:cache` / `npm run build` のデプロイ手順を整備する。
- ドキュメント更新：[`security-vulnerabilities.md`](../security-vulnerabilities.md) の HIGH 指摘（admin ルート未認証）は、現在の `routes/web.php` では修正済み。内容を更新する。

---

## 着手順の提案（たたき台）

1. テスト追加（L-4）
2. 小さく効果の大きい修正：H-6（綴りミス・リンク）、M-1 のログイン throttle と `/signup` の `auth` 化、M-2 の `config/app.php` の綴り
3. 整合性：H-1（ユニーク制約・インデックス）、H-5（トランザクション・メールのキュー化）、H-2・H-3（仕様決定後）
4. enum 化・ルール集約（M-3）→ rearchitecture-plan.md のステップ 2 以降
5. 申請単位の導入と `room_statuses` 廃止（M-4。設計合意が必要）

---

## 改訂履歴

| 日付 | 変更内容 |
|---|---|
| 2026-10-23 | 初版作成（コードレビュー結果と技術改善点調査を統合し、重複を排除して整理） |
