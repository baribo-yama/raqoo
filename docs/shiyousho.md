# ステータス表示仕様
## 概要
- ユーザーが予約申請などを行った際に、home.blade.phpで表示させる教室のステータス表示を、自分と自分以外のユーザーで表示させるステータスを変える

## 関連ファイル
- /home/hiraken/raqoo/raqoo/app/Http/Controllers/Admin/RoomStatusController.php
- raqoo/app/Models/Room.php
- raqoo/routes/web.php
- raqoo/resources/views/user/home.blade.php
- raqoo/resources/views/admin/dashboard.blade.php
- raqoo/public/user/home.js
- raqoo/public/user/home.css
- raqoo/public/admin/dashboard.css
- raqoo/public/admin/dashboard.js
- raqoo/database/migrations/2025_05_29_082840_create_reservations_table.php
- raqoo/database/migrations/2025_09_09_034917_create_room-statuses_table.php

## 仕様

### ユーザー画面（home.blade.php）

- 自分が予約申請を出したら、自分の画面ではその教室のステータスが「申請中」’pending’ になる
- 申請が通ったらその教室のステータスが「予約済み」’reserved’ になる
- 自分以外のユーザーがどこか教室を予約したら、その教室のステータスの表示は「利用不可」’unavailable’ になる
- そのほかの教室ステータスは「利用可能」’available’

自分の予約申請 → 「申請中」pending
自分の予約 → 「予約済み」reserved
他の人の予約 → 「利用不可」unavailable
それ以外（デフォルトステータス）→ 「利用可能」available


### 管理ダッシュボード (dashboard.blade.php)

- 予約申請が通って、ステータスが予約済み ‘reserved’ になったものだけがダッシュボードの対応する教室セルに「予約」と表示される
- 利用中 ‘in_use’, 申請中 ‘pending’ はここには表示しない。

全てのユーザーの「予約済み」reserved を対象教室のセルに反映させる

---

# 管理者ダッシュボードのセルクリック・モーダル
## 仕様
-モーダルに関して次の実装を行いたい。
もしダッシュボードのセルに「予約」がなく、空の場合にセルクリックをしたら、モーダルには授業名登録をするフォームが表示されるようにする。ここで登録した授業名はそのダッシュボードセルにも表示される。また、ユーザー画面の対象教室にも授業名が表示される。このとき授業が登録されている教室は「利用不可」unavailableとなる
もしダッシュボードセルに「予約」がある場合はモーダルに、その教室の予約者情報（予約代表者名、メンバー）を表示して、その下部には予約の「キャンセル」ボタンを配置する。このキャンセルボタンが押されると、ユーザーの予約がなくなる。つまりreservedからデフォルトのavailableに変わる
もしダッシュボードセルに授業が登録されていたら、セルクリックしたときに授業情報とその授業のキャンセルボタンが表示されるようにする。このキャンセルボタンを押した際にも、ステータスはunavailableからavailableに変わる。よってユーザー画面ではその教室は「利用可能」となる。

# 管理者ダッシュボード：セルクリック・モーダル実装仕様書

## 1. 概要
管理者ダッシュボードのタイムテーブルセル（予約枠）をクリックした際、セルの状態（空き・予約あり・授業あり）に応じて、表示するモーダルとアクションを動的に切り替える。

---

## 2. セル状態別の挙動定義

### A. セルが「空白（available）」の場合
- **表示内容**
    - 授業名登録フォーム（テキスト入力：`lecture_name`）
    - 登録ボタン
- **実行処理**
    - ステータスを `unavailable` に更新。
    - 入力された授業名をDBに保存。
- **反映箇所**
    - **管理者DSH:** 該当セルに「授業名」を表示。
    - **ユーザー画面:** 対象教室を「利用不可」とし、授業名（例：「アプリ制作」）を表示。

### B. セルが「予約済み（reserved）」の場合
- **表示内容**
    - 予約者情報（代表者名、同行メンバー一覧）
    - 「予約キャンセル」ボタン（管理者による強制キャンセル）
- **実行処理**
    - 対象の予約データを解除（削除またはステータス更新）。
    - ステータスを `available` に戻す。
- **反映箇所**
    - **管理者DSH/ユーザー画面:** セルおよび教室が「利用可能」に復帰。

### C. セルに「授業（unavailable）」が登録済みの場合
- **表示内容**
    - 登録済みの授業名表示
    - 「授業キャンセル（枠解放）」ボタン
- **実行処理**
    - 授業データをクリア。
    - ステータスを `available` に戻す。
- **反映箇所**
    - **管理者DSH/ユーザー画面:** セルおよび教室が「利用可能」に復帰。

---

## 3. 技術設計（Laravel）

### DB構成案
`reservations` テーブルに授業情報を統合する設計。

- **マイグレーション変更点**
    - `user_id`: nullableに変更（授業登録時はNULLを許容するため）
    - `is_lecture`: boolean (default: false)
    - `lecture_name`: string (nullable)

### 実装のヒント
- **Backend:** `updateOrCreate` メソッドを使用して、指定されたコマの予約/授業データを制御する。
- **Frontend:** セルのクリックイベントで `status` を受け取り、条件分岐（`switch`文等）で表示するモーダルの中身を切り替える。
