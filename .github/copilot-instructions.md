# Copilot Instructions for Raqoo Project

## プロジェクト概要
**Raqoo** は教室予約管理システム。Laravel 12 + Blade + Tailwind CSS で構築され、ユーザーと管理者の2つの認証システムを持つ。

## アーキテクチャ

### 認証システム（二重ガード）
- **ユーザー認証**: `auth:web` guard → `users` テーブル (`App\Models\User`)
- **管理者認証**: `auth:admin` guard → `admins` テーブル (`App\Models\Admin`)
- 設定: `config/auth.php` の `guards` セクション
- コントローラーで `$this->middleware('auth:admin')` または `auth` を使い分け

### ディレクトリ構造の重要な分離
```
app/Http/Controllers/
├── Admin/              # 管理者専用コントローラー（auth:admin必須）
│   ├── LoginController.php
│   ├── RoomController.php
│   ├── ReservationController.php
│   └── RoomStatusController.php
└── User/               # ユーザー専用コントローラー（auth:web）
    ├── RoomController.php
    ├── ReservationController.php
    └── NotificationController.php

resources/views/
├── admin/              # 管理者画面のBladeテンプレート
└── user/               # ユーザー画面のBladeテンプレート
    └── components/
        └── user/sidebar.blade.php  # <x-user.sidebar /> で呼び出し
```

### ルーティング規約（`routes/web.php`）
- **ユーザールート**: `/login`, `/home`, `/reservations/*`, `/notifications`
- **管理者ルート**: `/admin/login`, `/admin/dashboard`, `/admin/reservations/*`, `/admin/rooms`
- 名前付きルート例: `route('user.reservations.index')`, `route('admin.reservations.approve')`
- 同名コントローラーの衝縮回避: `as AdminRoomController` / `as UserRoomController` でエイリアス

### データモデルとリレーション
```php
// 予約フロー: User → Reservation → Room
Reservation::class {
    status: 'pending' | 'reserved' | 'in_use' | 'cancelled' | 'finished'
    period: 1-6限 (tinyInteger)
    members: text (改行区切り)
    rejection_reason: nullable
}

Room::class {
    hasMany(RoomStatus)
    getStatusForPeriod($date, $period)  // 時間帯別の教室ステータス取得
}
```

### Bladeコンポーネント規約
- 配置: `resources/views/components/user/sidebar.blade.php`
- 呼び出し: `<x-user.sidebar />` (ケバブケースに自動変換)
- データ渡し: `@props(['unreadCount'])` で受け取り

## 開発ワークフロー

### 環境構築
```bash
# Dockerコンテナ起動
docker-compose up -d

# PHPコンテナに入る
docker exec -it app-appli4 bash

# 初回セットアップ
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed  # 教室データ等の初期投入

# フロントエンド
npm install
npm run dev  # Vite開発サーバー起動
```

### アクセスURL
- ユーザー画面: `http://localhost:8881/home`
- 管理者画面: `http://localhost:8881/admin/dashboard`
- データベース: `localhost:8307` (mysql, root/root, dbuser/dbuser)

### デバッグ手法
- `Log::info('メッセージ', ['key' => $value])` → `storage/logs/laravel.log`
- `dd($variable)` でダンプ&終了
- コントローラーで `// デバッグ用` コメント残す習慣あり（例: `ReservationController::create()`）

### マイグレーション命名規則
- 日付プレフィックス: `2025_05_29_082840_create_reservations_table.php`
- `up()` で `Schema::create()`, `down()` で `Schema::dropIfExists()`

## コーディング規約

### 必須回答フォーマット
変更提案時は必ず以下を明記:

```markdown
**Target file:** `raqoo/app/Http/Controllers/User/ReservationController.php`

**Change scope:**
- Location type: method
- Identifier: public function store(Request $request)
- Nearest unique lines:
  - before: `Log::info('予約申請開始', [`
  - after: `'date' => $request->date,`
- Expected line range: ~L98-L110

**Patch:**
```diff
--- a/raqoo/app/Http/Controllers/User/ReservationController.php
+++ b/raqoo/app/Http/Controllers/User/ReservationController.php
@@ -105,6 +105,9 @@
+        // 重複予約の最終チェック
+        if (Reservation::isDuplicate($request->room_id, $request->date, $request->period)) {
+            return back()->withErrors(['error' => '予約が重複しています']);
+        }
```
```

### ファイルパス参照
- リポジトリルートからの絶対パス: `raqoo/app/Models/User.php`
- Bladeでのasset参照: `{{ asset('user/home.css') }}` → `public/user/home.css`

### 認証チェックのベストプラクティス
```php
// コントローラーのコンストラクタで統一
public function __construct()
{
    $this->middleware('auth');        // ユーザー
    // または
    $this->middleware('auth:admin');  // 管理者
}
```

### バリデーション規約
```php
$validated = $request->validate([
    'date' => 'required|date|after_or_equal:today',
    'period' => 'required|integer|min:1|max:6',
    'members' => 'nullable|string',
]);
```

## プロジェクト固有パターン

### 予約ステータス遷移
```
pending → approved (管理者承認) → in_use → finished
       ↘ rejected (拒否理由付き)
       ↘ cancelled (ユーザーキャンセル)
```

### 時間帯（period）の扱い
- 1-6限を整数で管理（1限=1, 2限=2...）
- `RoomStatus::getStatusForRoom($roomId, $date, $period)` で特定時間の状態取得

### メンバー入力処理
```php
// 改行区切りのメンバーを配列化
$members = preg_split('/[\n\r]+/', trim($validated['members']));
$memberCount = 1 + count(array_filter($members, 'trim'));  // 代表者+メンバー
```

### 静的アセット配置
- CSS/JS: `public/admin/`, `public/user/` に分離
- Vite管理: `resources/css/app.css`, `resources/js/app.js`（未使用の場合もある）
- Ionicons CDN: `<script type="module" src="https://unpkg.com/ionicons@7.1.0/..."></script>`

## よくある問題と対処

### Namespace Collision
同名コントローラーは必ずエイリアス:
```php
use App\Http\Controllers\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\User\RoomController as UserRoomController;
```

### Blade Component Not Found
- ファイル名: `resources/views/components/user/sidebar.blade.php`
- 呼び出し: `<x-user.sidebar />` (ドット記法とケバブケース)

### Docker環境でのマイグレーション失敗
```bash
# コンテナ内で実行
docker exec -it app-appli4 php artisan migrate:fresh --seed
```

## 参考ファイル
- 認証設定: `raqoo/config/auth.php`
- ルート定義: `raqoo/routes/web.php`
- 初期データ: `docker/db/sql/init.sql` (platform DB作成、dbuser権限設定)
- Docker構成: `docker-compose.yml` (ポート8881, DB 8307)
