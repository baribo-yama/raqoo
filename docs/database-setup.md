# データベースセットアップガイド

## 📌 目的

このドキュメントは、Raqooプロジェクトで**マイグレーション**と**シーダー**を実行する手順をまとめたものです。
初めて環境構築する場合や、データベース構造を変更した場合に参照してください。

---

## 🐳 前提条件

### 必要な環境
- Docker がインストールされている
- Docker Compose がインストールされている
- プロジェクトルート（`/home/hiraken/raqoo`）に移動済み

### Docker コンテナの起動確認

```bash
# 現在のディレクトリを確認
pwd
# 出力: /home/hiraken/raqoo

# Docker コンテナを起動
docker-compose up -d

# コンテナの起動状態を確認
docker-compose ps
```

**期待される出力:**
```
NAME                COMMAND                  SERVICE             STATUS              PORTS
app-appli4          "docker-php-entrypoi…"   app-appli4          running
db-appli4           "docker-entrypoint.s…"   db-appli4           running             0.0.0.0:8307->3306/tcp
nginx-appli4        "/docker-entrypoint.…"   nginx-appli4        running             0.0.0.0:8881->80/tcp
```

---

## 🔧 基本操作: PHPコンテナへの接続

### コンテナに入る

```bash
# PHPコンテナに入る
docker exec -it app-appli4 bash
```

**成功すると、プロンプトが以下のように変わります:**
```bash
root@abc123:/var/www#
```

### コンテナ内での作業ディレクトリ確認

```bash
# 現在のディレクトリを確認（/var/www にいることを確認）
pwd
# 出力: /var/www

# Laravelプロジェクトディレクトリに移動
cd /var/www/raqoo

# ファイル一覧を確認
ls -la
# artisan ファイルが存在することを確認
```

---

## 📊 マイグレーション実行方法

### 1. 初回セットアップ（全テーブル作成）

**シナリオ:** 初めてプロジェクトをクローンした、または DB を完全にリセットしたい場合

```bash
# PHPコンテナに入る
docker exec -it app-appli4 bash

# Laravelプロジェクトディレクトリに移動
cd /var/www/raqoo

# マイグレーション実行（既存データは保持）
php artisan migrate
```

**出力例:**
```
   INFO  Preparing database.

  Creating migration table ........................ 26ms DONE

   INFO  Running migrations.

  2025_05_28_082936_create_rooms_table ............ 45ms DONE
  2025_05_29_082242_create_users_table ............ 38ms DONE
  2025_05_29_082840_create_reservations_table ..... 52ms DONE
  2025_05_29_083130_create_notifications_table .... 31ms DONE
  2025_09_09_034105_create_admins_table ........... 29ms DONE
  2025_09_09_034917_create_room-statuses_table .... 33ms DONE
```

---

### 2. データベース完全リセット + マイグレーション

**シナリオ:** 開発中にテストデータを削除して、真っ新な状態から始めたい場合

**⚠️ 警告: すべてのデータが削除されます！本番環境では絶対に実行しないでください！**

```bash
# PHPコンテナ内で実行
cd /var/www/raqoo

# すべてのテーブルを削除して再作成
php artisan migrate:fresh
```

**確認プロンプトが表示された場合:**
```
Do you really wish to run this command? (yes/no) [no]:
> yes
```

**出力例:**
```
   INFO  Dropping all tables.

  Dropped all tables successfully.

   INFO  Preparing database.

  Creating migration table ........................ 23ms DONE

   INFO  Running migrations.

  2025_05_28_082936_create_rooms_table ............ 41ms DONE
  ...
```

---

### 3. マイグレーションのロールバック

**シナリオ:** 最後に実行したマイグレーションを取り消したい場合

```bash
# 直前のマイグレーションを1つだけ戻す
php artisan migrate:rollback --step=1

# 確認
php artisan migrate:status
```

**出力例:**
```
Migration name                                  Batch  Status
2025_05_28_082936_create_rooms_table            1      Ran
2025_05_29_082242_create_users_table            1      Ran
2025_09_09_034917_create_room-statuses_table    2      Pending (← ロールバックされた)
```

---

### 4. マイグレーション状態の確認

```bash
# どのマイグレーションが実行済みか確認
php artisan migrate:status
```

**出力例:**
```
Migration name                                  Batch  Status
2025_05_28_082936_create_rooms_table            1      Ran
2025_05_29_082242_create_users_table            1      Ran
2025_05_29_082840_create_reservations_table     1      Ran
2025_05_29_083130_create_notifications_table    1      Ran
2025_09_09_034105_create_admins_table           1      Ran
2025_09_09_034917_create_room-statuses_table    1      Ran
```

---

## 🌱 シーダー実行方法

### 1. 全シーダーを実行（初期データ投入）

**シナリオ:** マイグレーション後に初期データ（教室リスト、管理者アカウントなど）を投入する

```bash
# PHPコンテナ内で実行
cd /var/www/raqoo

# 全シーダーを実行
php artisan db:seed
```

**出力例:**
```
   INFO  Seeding database.

  Database\Seeders\RoomSeeder ...................... RUNNING
  Database\Seeders\RoomSeeder .................. 125.32 ms DONE

  Database\Seeders\AdminSeeder ..................... RUNNING
  Database\Seeders\AdminSeeder ................... 98.45 ms DONE
```

---

### 2. 特定のシーダーだけを実行

**シナリオ:** 教室データだけを追加したい、管理者アカウントだけを作成したい場合

```bash
# 教室データのみ投入
php artisan db:seed --class=RoomSeeder

# 管理者アカウントのみ作成
php artisan db:seed --class=AdminSeeder

# ユーザーアカウントのみ作成（存在する場合）
php artisan db:seed --class=UserSeeder
```

---

### 3. マイグレーション + シーダーを一度に実行

**シナリオ:** データベースを完全リセットして、初期データも一緒に投入したい場合

```bash
# すべてのテーブルを削除 → 再作成 → 初期データ投入
php artisan migrate:fresh --seed
```

**⚠️ 警告: すべてのデータが削除されます！**

**出力例:**
```
   INFO  Dropping all tables.

  Dropped all tables successfully.

   INFO  Preparing database.

   INFO  Running migrations.

  2025_05_28_082936_create_rooms_table ............ 42ms DONE
  ...

   INFO  Seeding database.

  Database\Seeders\RoomSeeder .................. 123.45 ms DONE
  Database\Seeders\AdminSeeder .................. 95.67 ms DONE
```

---

### 4. シーダーで投入されるデータの確認

**データベースに直接接続して確認:**

```bash
# コンテナ外（ホストマシン）から接続
mysql -h 127.0.0.1 -P 8307 -u dbuser -p
# パスワード: dbuser

# データベースを選択
use platform;

# テーブル一覧を表示
SHOW TABLES;

# 教室データを確認
SELECT * FROM rooms;

# 管理者アカウントを確認
SELECT id, email, created_at FROM admins;

# 終了
exit;
```

---

## 🔄 よくある操作パターン

### パターン1: 初めてプロジェクトをセットアップする

```bash
# 1. Dockerコンテナ起動
docker-compose up -d

# 2. PHPコンテナに入る
docker exec -it app-appli4 bash

# 3. Laravelディレクトリに移動
cd /var/www/raqoo

# 4. マイグレーション + シーダー実行
php artisan migrate:fresh --seed

# 5. 完了（ブラウザで http://localhost:8881 にアクセス）
exit
```

---

### パターン2: 新しいマイグレーションファイルが追加された

**シナリオ:** チームメンバーが新しいテーブルを追加して、`git pull` した場合

```bash
# 1. PHPコンテナに入る
docker exec -it app-appli4 bash

# 2. Laravelディレクトリに移動
cd /var/www/raqoo

# 3. 新しいマイグレーションだけを実行
php artisan migrate

# 4. 完了
exit
```

---

### パターン3: テストデータをリセットしたい（開発中）

```bash
# 1. PHPコンテナに入る
docker exec -it app-appli4 bash

# 2. Laravelディレクトリに移動
cd /var/www/raqoo

# 3. データベース完全リセット + 初期データ投入
php artisan migrate:fresh --seed

# 4. 完了
exit
```

---

### パターン4: 特定のシーダーだけを再実行したい

```bash
# 1. PHPコンテナに入る
docker exec -it app-appli4 bash

# 2. Laravelディレクトリに移動
cd /var/www/raqoo

# 3. 特定のシーダーだけ実行
php artisan db:seed --class=RoomSeeder

# 4. 完了
exit
```

---

## 🚨 トラブルシューティング

### エラー1: `SQLSTATE[HY000] [2002] Connection refused`

**原因:** データベースコンテナが起動していない

**解決方法:**
```bash
# コンテナの状態を確認
docker-compose ps

# db-appli4 が起動していない場合
docker-compose up -d db-appli4

# 10秒待ってから再度マイグレーション実行
php artisan migrate
```

---

### エラー2: `Access denied for user 'root'@'localhost'`

**原因:** `.env` ファイルのデータベース設定が間違っている

**解決方法:**
```bash
# .env ファイルを確認
cat /var/www/raqoo/.env | grep DB_

# 正しい設定:
# DB_CONNECTION=mysql
# DB_HOST=db-appli4        # ← コンテナ名
# DB_PORT=3306             # ← コンテナ内部ポート
# DB_DATABASE=platform     # ← docker/db/sql/init.sql で作成されたDB
# DB_USERNAME=dbuser       # ← init.sql で作成されたユーザー
# DB_PASSWORD=dbuser
```

**修正後:**
```bash
# 設定キャッシュをクリア
php artisan config:clear

# 再度マイグレーション実行
php artisan migrate
```

---

### エラー3: `Nothing to migrate`

**原因:** すでにすべてのマイグレーションが実行済み

**確認方法:**
```bash
# マイグレーション状態を確認
php artisan migrate:status
```

**解決方法:**
- 新しいマイグレーションがない場合、これは正常です
- データをリセットしたい場合は `php artisan migrate:fresh --seed`

---

### エラー4: `Class "Database\Seeders\RoomSeeder" not found`

**原因:** シーダーファイルが存在しない、または autoload されていない

**解決方法:**
```bash
# Composer のオートロードを再生成
composer dump-autoload

# シーダーファイルが存在するか確認
ls -la /var/www/raqoo/database/seeders/

# 再度シーダー実行
php artisan db:seed
```

---

### エラー5: コンテナに入れない（`Error: No such container: app-appli4`）

**原因:** コンテナが起動していない

**解決方法:**
```bash
# プロジェクトルートにいることを確認
cd /home/hiraken/raqoo

# コンテナを起動
docker-compose up -d

# コンテナ一覧を確認
docker ps -a

# コンテナ名を確認して再度入る
docker exec -it app-appli4 bash
```

---

## 📝 コマンド一覧（チートシート）

### マイグレーション

| コマンド | 説明 | 本番使用 |
|---------|------|---------|
| `php artisan migrate` | 未実行のマイグレーションを実行 | ✅ 可 |
| `php artisan migrate:fresh` | 全テーブル削除 → 再作成 | ❌ 禁止 |
| `php artisan migrate:fresh --seed` | 全テーブル削除 → 再作成 → 初期データ投入 | ❌ 禁止 |
| `php artisan migrate:rollback` | 最後のマイグレーションを取り消し | ⚠️ 注意 |
| `php artisan migrate:rollback --step=1` | 1つだけロールバック | ⚠️ 注意 |
| `php artisan migrate:status` | マイグレーション状態を確認 | ✅ 可 |

### シーダー

| コマンド | 説明 | 本番使用 |
|---------|------|---------|
| `php artisan db:seed` | 全シーダーを実行 | ⚠️ 注意 |
| `php artisan db:seed --class=RoomSeeder` | 特定のシーダーのみ実行 | ⚠️ 注意 |

### その他

| コマンド | 説明 |
|---------|------|
| `php artisan config:clear` | 設定キャッシュをクリア |
| `composer dump-autoload` | オートロードを再生成 |
| `docker exec -it app-appli4 bash` | PHPコンテナに入る |
| `exit` | コンテナから出る |

---

## 🔐 本番環境での注意事項

### ⚠️ 絶対にやってはいけないこと

```bash
# ❌ 本番環境で実行禁止
php artisan migrate:fresh       # 全データ削除！
php artisan migrate:fresh --seed # 全データ削除！
php artisan migrate:rollback    # データ消失の可能性
```

### ✅ 本番環境で安全なコマンド

```bash
# ✅ 本番環境で実行OK（新しいマイグレーションのみ実行）
php artisan migrate

# ✅ マイグレーション状態確認
php artisan migrate:status

# ✅ 設定キャッシュクリア（トラブル時）
php artisan config:clear
php artisan cache:clear
```

### 本番デプロイ時の推奨手順

```bash
# 1. バックアップ取得
mysqldump -h 127.0.0.1 -P 8307 -u dbuser -p platform > backup_$(date +%Y%m%d_%H%M%S).sql

# 2. マイグレーション実行（新規テーブル・カラム追加のみ）
docker exec -it app-appli4 bash
cd /var/www/raqoo
php artisan migrate --force  # 本番では --force が必要

# 3. 動作確認

# 4. 問題があればロールバック
php artisan migrate:rollback --step=1
```

---

## 📚 参考情報

### プロジェクト固有の設定

- **データベース名:** `platform`
- **データベースユーザー:** `dbuser` / `dbuser`
- **データベースポート（外部）:** `8307`
- **データベースポート（コンテナ内）:** `3306`
- **PHPコンテナ名:** `app-appli4`
- **DBコンテナ名:** `db-appli4`
- **初期データSQL:** `docker/db/sql/init.sql`

### 関連ドキュメント

- [開発ルール](./development-rules.md) - Git運用、命名規則など
- [Copilot Instructions](../.github/copilot-instructions.md) - プロジェクト技術仕様
- [Laravel公式ドキュメント - Migrations](https://laravel.com/docs/12.x/migrations)
- [Laravel公式ドキュメント - Seeding](https://laravel.com/docs/12.x/seeding)

---

## 📝 改訂履歴

| 日付 | 変更内容 | 担当者 |
|------|---------|--------|
| 2025-11-23 | 初版作成 | - |

---

**このドキュメントに関する質問・不明点は、チームで共有してください。**
