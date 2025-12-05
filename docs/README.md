# Raqoo - 教室予約管理システム

## 📖 概要

Raqooは、教室の予約を効率的に管理するためのWebアプリケーションです。
学生が教室を予約申請し、管理者が承認・却下するワークフローを提供します。

---

## 🚀 クイックスタート

### 1. 環境構築

```bash
# プロジェクトルートに移動
cd /home/hiraken/raqoo

# Dockerコンテナ起動
docker-compose up -d

# PHPコンテナに入る
docker exec -it app-appli4 bash

# Laravelディレクトリに移動
cd /var/www/raqoo

# 依存パッケージインストール
composer install

# データベース初期化（マイグレーション + 初期データ投入）
php artisan migrate:fresh --seed

# コンテナから出る
exit
```

### 2. アプリケーションにアクセス

- **ユーザー画面:** http://localhost:8881/home
- **管理者画面:** http://localhost:8881/admin/dashboard

---

## 🔑 ログイン情報

### ユーザーアカウント

初期データとして以下のテストユーザーが作成されます（`database/seeders/UserSeeder.php`）：

| 名前 | メールアドレス | パスワード | 学籍番号 |
|------|---------------|-----------|---------|
| ばやし | `bayashi@example.com` | `123456` | ST001 |
| 佐藤花子 | `sato@example.com` | `password123` | ST002 |
| 山田次郎 | `yamada@example.com` | `password123` | ST003 |

**ログインURL:** http://localhost:8881/login

### 管理者アカウント

初期データとして以下の管理者アカウントが作成されます（`database/seeders/AdminSeeder.php`）：

| メールアドレス | パスワード | 役割 |
|---------------|-----------|------|
| `admin@raqoo.com` | `admin123` | 管理者 |
| `manager@raqoo.com` | `manager123` | 管理者 |

**ログインURL:** http://localhost:8881/admin/login

---

## 📱 基本的な使い方

### ユーザー側の操作

1. **ログイン**
   - http://localhost:8881/login にアクセス
   - メールアドレスとパスワードを入力

2. **教室を予約する**
   - ホーム画面から「新規予約」をクリック
   - 日付、時間帯（1-6限）、教室を選択
   - 利用目的とメンバーを入力して申請

3. **予約状況を確認する**
   - サイドバーの「予約一覧」から現在の予約を確認
   - ステータス: 承認待ち / 承認済み / 却下

4. **予約をキャンセルする**
   - 予約一覧から対象の予約を選択
   - 「キャンセル」ボタンをクリック（承認前のみ可能）

### 管理者側の操作

1. **ログイン**
   - http://localhost:8881/admin/login にアクセス
   - 管理者メールアドレスとパスワードを入力

2. **予約を承認・却下する**
   - ダッシュボードで予約申請一覧を確認
   - 「承認」または「却下」をクリック
   - 却下の場合は理由を入力

3. **教室の予約状況を確認する**
   - ダッシュボードのカレンダーとテーブルで一覧表示
   - 日付・時間帯ごとの予約状況を確認

---

## 🗂️ プロジェクト構成

```
raqoo/
├── app/
│   ├── Http/Controllers/
│   │   ├── Admin/          # 管理者機能
│   │   └── User/           # ユーザー機能
│   └── Models/             # データモデル
├── database/
│   ├── migrations/         # データベース構造
│   └── seeders/            # 初期データ
│       ├── AdminSeeder.php  # 管理者アカウント
│       ├── UserSeeder.php   # ユーザーアカウント
│       └── RoomSeeder.php   # 教室データ
├── resources/views/
│   ├── admin/              # 管理者画面
│   └── user/               # ユーザー画面
├── public/css/             # スタイルシート
└── routes/web.php          # ルーティング定義
```

---

## 🛠️ よく使うコマンド

### データベース操作

```bash
# データベースをリセット（全データ削除 + 初期データ投入）
docker exec -it app-appli4 bash
cd /var/www/raqoo
php artisan migrate:fresh --seed
exit

# マイグレーションのみ実行
php artisan migrate

# シーダーのみ実行
php artisan db:seed
```

### キャッシュクリア

```bash
docker exec -it app-appli4 bash
cd /var/www/raqoo
php artisan config:clear
php artisan cache:clear
exit
```

### Dockerコンテナ操作

```bash
# コンテナ起動
docker-compose up -d

# コンテナ停止
docker-compose down

# コンテナ状態確認
docker-compose ps
```

---

## 📚 詳細ドキュメント

- [開発ルール](./development-rules.md) - ブランチ命名規則、PR運用など
- [データベースセットアップ](./database-setup.md) - マイグレーション・シーダーの詳細
- [技術仕様](../.github/copilot-instructions.md) - アーキテクチャ、認証システムなど

---

## ⚙️ 技術スタック

- **フレームワーク:** Laravel 12
- **データベース:** MySQL 8.0
- **フロントエンド:** Blade, Tailwind CSS（一部）
- **インフラ:** Docker, Docker Compose
- **Webサーバー:** Nginx
- **PHP:** 8.3

---

## 🐛 トラブルシューティング

### ログインできない

- データベースが初期化されているか確認: `php artisan migrate:fresh --seed`
- ブラウザのキャッシュをクリア（Ctrl+Shift+R）

### 画面が真っ白になる

- Laravelログを確認: `tail -f raqoo/storage/logs/laravel.log`
- コンテナが起動しているか確認: `docker-compose ps`

### データベース接続エラー

- `.env` ファイルの設定を確認:
  ```
  DB_HOST=db-appli4
  DB_PORT=3306
  DB_DATABASE=platform
  DB_USERNAME=dbuser
  DB_PASSWORD=dbuser
  ```

---

## 📞 サポート

質問や問題が発生した場合は、チームのコミュニケーションツール（Slack/Discord等）で共有してください。

---

**開発チーム: baribo-yama/raqoo**
