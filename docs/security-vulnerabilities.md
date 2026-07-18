# セキュリティ脆弱性レポート

調査日: 2026-06-26  
対象ブランチ: fix/reservationController  
調査範囲: リポジトリ全体

---

## サマリー

| 重要度 | 件数 |
|--------|------|
| HIGH   | 1    |
| MEDIUM | 0    |
| LOW    | 0    |

---

## 脆弱性一覧

### [HIGH] 管理者ルートへの未認証アクセス

**ファイル**: `routes/web.php` L97, L100-102  
**カテゴリ**: 認証バイパス

#### 問題のコード

```php
// auth:admin ミドルウェアが付いていない
Route::get('/admin/rooms', [AdminDashBoardController::class, 'roomsIndex'])
    ->name('admin.rooms.index');

Route::get('/admin/notifications', function() {
    return view('admin.notifications-index');
})->name('admin.notifications.index');
```

他の管理者ルートは `Route::middleware(['auth:admin'])->group(...)` または個別に `.middleware('auth:admin')` で保護されているが、この2ルートはそのグループ外に単独で定義されており、ミドルウェアが適用されていない。

#### 攻撃シナリオ

1. 一般ユーザーとしてログイン
2. ブラウザで `/admin/rooms` または `/admin/notifications` に直接アクセス
3. 管理者ページが表示される

#### 修正方法

既存の `auth:admin` グループに移動する。

```php
Route::prefix('admin')->name('admin.')->middleware('auth:admin')->group(function () {
    // 既存のルート...

    // 以下を追加
    Route::get('/rooms', [AdminDashBoardController::class, 'roomsIndex'])
        ->name('rooms.index');
    Route::get('/notifications', function () {
        return view('admin.notifications-index');
    })->name('notifications.index');
});
```

---

## 安全が確認された箇所

調査の中で懸念として挙がったが、実際には安全に実装されていた箇所。

| 項目 | 実装箇所 | 内容 |
|------|----------|------|
| ユーザー予約のIDOR | `ReservationController.php` L361, L373 | `show()` と `cancel()` で `$reservation->user_id !== Auth::id()` による所有者確認あり |
| ユーザールートの認証 | `routes/web.php` L106 | `middleware('auth', 'verified')` グループで全ルートを保護 |
| SQLインジェクション（一括操作） | `Admin/ReservationController.php` L46, L102 | `whereIn()` はパラメータバインディングを使用しており注入不可 |
| マスアサインメント | `Models/Reservation.php`, 各Controller | `Reservation::create($request->all())` は使用されておらず、全て明示的な配列で `create()` を呼んでいる |
| XSS | 全Bladeテンプレート | `{{ }}` による自動エスケープが有効 |
| CSRFトークン | フォーム全般 | Laravelデフォルトの保護が有効 |
| オープンリダイレクト | `PrecautionsController.php` `sanitizeNext()` | 内部パス（`/`始まり）のみ許可する検証あり |
| パスワード管理 | 認証フロー全般 | `Hash::make()` によるハッシュ化を使用 |
| APP_FAKE_NOW の悪用 | `TimeGuardMiddleware.php` | 環境変数はHTTPリクエストから変更不可のため悪用不可 |

---

## 認可の実装状況

### 現在の実装方式

Gate・Policy は使用されていない。認可は以下の2つの方式で実装されている。

**1. ミドルウェアによるロールガード**

```
一般ユーザー → middleware('auth', 'verified')
管理者       → middleware('auth:admin')
```

**2. コントローラー内の手動所有者チェック**

```php
// ReservationController.php
if ($reservation->user_id !== Auth::id()) {
    abort(403);
}
```

### Gate/Policy を使わない判断について

現状はロールが「一般ユーザー」と「管理者」の2種類のみで、所有者チェックも `show()` と `cancel()` の2箇所に限られている。ルールがシンプルなため、現在の手動チェックで十分。

以下のような条件が生まれた場合は Policy への切り出しを検討する：
- 同じ所有者チェックが3箇所以上に増える
- 「特定の管理者しか操作できない」などの複合条件が生まれる
