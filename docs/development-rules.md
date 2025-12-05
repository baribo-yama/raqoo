# Raqoo プロジェクト開発ルール

## 📌 目的

このドキュメントは、チーム開発を円滑に進めるための**最低限守るべき5つのルール**を定めたものです。
すべてのチームメンバーは、コードを書く前にこのルールを確認してください。

---

## 🎯 最低限守るべき5つのルール

### 1. ブランチ命名規則

#### ルール
```
feature/{担当領域}-{機能名}    # 新機能開発
fix/{問題箇所}-{修正内容}      # バグ修正
refactor/{対象}-{目的}         # リファクタリング
```

#### 具体例
```bash
# 良い例
git checkout -b feature/user-reservation-form
git checkout -b fix/admin-dashboard-layout
git checkout -b refactor/css-common-components

# 悪い例
git checkout -b my-branch          # 何をする枝か不明
git checkout -b fix                # 修正内容が不明
git checkout -b feature123         # 番号だけでは意味不明
```

#### なぜ必要か
- **コンフリクト回避:** ブランチ名で誰が何を作業中か一目瞭然
- **レビュー効率化:** Pull Request時に目的が即座に理解できる
- **作業の可視化:** `git branch -a` で全員の作業状況を把握

#### チェックリスト
- [ ] ブランチ名が `feature/`, `fix/`, `refactor/` のいずれかで始まっている
- [ ] ブランチ名から何を実装・修正するか推測できる
- [ ] 日本語ではなく英語（ローマ字でもOK）で記述されている

---

### 2. Pull Request（PR）のサイズ上限

#### ルール
**1つのPRで変更する行数は最大300行まで**

#### 理由
- 300行を超えるとレビュアーの集中力が続かない
- 大きすぎるPRはバグの見落としリスクが高い
- 小さいPRは問題発生時のロールバックが容易

#### 300行を超える場合の対処法

**方法1: 機能を分割**
```bash
# 悪い例: 1つのPRで全部実装
feature/reservation-system (500行)

# 良い例: 機能ごとに分割
feature/reservation-form      (150行)
feature/reservation-confirm   (100行)
feature/reservation-store     (120行)
```

**方法2: 段階的にマージ**
```
Phase 1: データベース設計（マイグレーション） → マージ
Phase 2: バックエンドロジック（コントローラー） → マージ
Phase 3: フロントエンド（Blade + CSS）         → マージ
```

#### PRテンプレート（必須項目）

PRを作成する際は、以下の項目を必ず記載してください：

```markdown
## 変更内容
- [ ] ユーザー予約フォームにメンバー入力欄を追加

## 変更理由
複数人での予約に対応するため

## 影響範囲
- `app/Http/Controllers/User/ReservationController.php`
- `resources/views/user/reservations/create.blade.php`
- `public/css/user/reservations.css`

## テスト確認項目
- [ ] メンバー欄を空欄で送信できる
- [ ] 改行区切りで複数メンバーを登録できる
- [ ] 既存の予約機能に影響がない

## スクリーンショット（画面変更時）
[画像を添付]
```

#### チェックリスト
- [ ] 変更行数が300行以内（`git diff --stat` で確認）
- [ ] PRテンプレートの全項目を記入済み
- [ ] 最低1人のレビュアーを指定
- [ ] テスト確認項目をすべてチェック済み

---

### 3. コミット前の `dd()` 削除チェック

#### ルール
**コミット前に必ず `dd()`, `dump()`, `var_dump()` を削除する**

#### なぜ危険か
```php
// このコードが本番環境に入ると...
public function store(Request $request)
{
    dd($request->all());  // ← ここで処理が止まる！

    // 以降のコードが実行されない
    Reservation::create($validated);
}
```

**結果:** ユーザーに白い画面（デバッグ情報）が表示され、予約ができない

#### 確認方法

**コミット前に実行するコマンド:**
```bash
# PHPファイル内の dd() を検索
grep -rn "dd(" app/ resources/

# dump() も検索
grep -rn "dump(" app/ resources/

# var_dump() も検索
grep -rn "var_dump(" app/ resources/
```

**何も出力されなければOK**

#### 代わりに使うべきもの

```php
// ❌ 本番に残してはいけない
dd($variable);
var_dump($variable);

// ✅ ログファイルに出力（本番でも安全）
Log::info('予約作成開始', [
    'user_id' => auth()->id(),
    'room_id' => $request->room_id,
]);

// ✅ 開発環境でのみ出力
if (config('app.debug')) {
    dump($variable);
}
```

#### チェックリスト
- [ ] `grep -rn "dd(" app/` を実行して何も出ない
- [ ] `grep -rn "dump(" app/` を実行して何も出ない
- [ ] ログ出力は `Log::info()` または `Log::error()` を使用

---

### 4. 命名規則の統一

#### ルール

| 言語 | 対象 | 命名規則 | 例 |
|------|------|----------|-----|
| PHP | 変数名 | キャメルケース | `$userId`, `$reservationList` |
| PHP | メソッド名 | キャメルケース（動詞始まり） | `getUserReservations()`, `createReservation()` |
| PHP | クラス名 | パスカルケース | `ReservationController`, `ReservationService` |
| Blade | 変数出力 | キャメルケース | `{{ $unreadCount }}` |
| CSS | クラス名 | BEM記法（ケバブケース） | `.reservation-card`, `.reservation-card__title` |

#### BEM記法の詳細

**Block（ブロック）:** 独立したコンポーネント
```css
.card { }
.button { }
.header { }
```

**Element（要素）:** ブロックの子要素（`__` で接続）
```css
.card__header { }
.card__title { }
.card__content { }
.button__icon { }
```

**Modifier（修飾子）:** 状態やバリエーション（`--` で接続）
```css
.card--highlighted { }
.button--primary { }
.button--disabled { }
```

#### 具体例

**HTML/Blade:**
```blade
<div class="reservation-card">
    <div class="reservation-card__header">
        <h3 class="reservation-card__title">{{ $room->name }}</h3>
        <span class="badge badge--pending">承認待ち</span>
    </div>
    <div class="reservation-card__content">
        <p>{{ $reservation->purpose }}</p>
    </div>
</div>
```

**CSS:**
```css
/* Block */
.reservation-card {
    background: white;
    border-radius: 8px;
    padding: 16px;
}

/* Element */
.reservation-card__header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
}

.reservation-card__title {
    font-size: 18px;
    font-weight: bold;
}

/* Modifier */
.badge--pending {
    background-color: #fef3c7;
    color: #92400e;
}
```

#### 禁止パターン

```css
/* ❌ キャメルケース（JavaScriptではないのでNG） */
.reservationCard { }
.cardTitle { }

/* ❌ スネークケース（読みづらい） */
.reservation_card { }
.card_title { }

/* ❌ 深すぎる階層 */
.reservation-card__header__title__text { }
```

#### チェックリスト
- [ ] PHP変数はキャメルケース（`$userId`）
- [ ] CSSクラスはBEM記法（`.card__title`）
- [ ] メソッド名は動詞で始まる（`getUser`, `createReservation`）

---

### 5. 共通レイアウトの必須継承

#### ルール
**すべてのBladeファイルは `layouts/app.blade.php` を継承する**

#### 標準テンプレート

```blade
{{-- resources/views/user/reservations/index.blade.php --}}

{{-- 1. 必ず layouts/app.blade.php を継承 --}}
@extends('layouts.app')

{{-- 2. ページタイトルを指定 --}}
@section('title', '予約一覧 - Raqoo')

{{-- 3. ページ固有のCSSを追加 --}}
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/user/reservations.css') }}">
@endpush

{{-- 4. メインコンテンツ --}}
@section('content')
    <div class="container">
        <h1>予約一覧</h1>

        {{-- コンテンツ --}}
    </div>
@endsection

{{-- 5. ページ固有のJavaScriptを追加 --}}
@push('scripts')
    <script src="{{ asset('js/user/reservations.js') }}"></script>
@endpush
```

#### 共通レイアウト（`layouts/app.blade.php`）の構造

```blade
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Raqoo - 教室予約システム')</title>

    {{-- 全画面共通CSS --}}
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components.css') }}">

    {{-- ページ固有CSS --}}
    @stack('styles')
</head>
<body>
    {{-- メインコンテンツ --}}
    @yield('content')

    {{-- 全画面共通JavaScript --}}
    <script src="{{ asset('js/common.js') }}"></script>

    {{-- ページ固有JavaScript --}}
    @stack('scripts')
</body>
</html>
```

#### なぜ必要か

**Before（共通レイアウトなし）:**
```blade
{{-- user/home.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ホーム</title>
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
</head>
...

{{-- user/reservations/index.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>予約一覧</title>
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">  <!-- 重複！ -->
</head>
...
```

**問題点:**
- `<head>` の内容が12ファイルすべてに重複
- Google Analytics追加時に12ファイルすべてを修正
- `<meta name="viewport">` の記述漏れでスマホ対応がバラバラ

**After（共通レイアウトあり）:**
- `layouts/app.blade.php` を1回修正すれば全画面に反映
- 新規Blade作成時の記述量が80%削減

#### チェックリスト
- [ ] Bladeの1行目に `@extends('layouts.app')` がある
- [ ] `@section('title')` でページタイトルを指定
- [ ] `@section('content')` でコンテンツを記述
- [ ] `<!DOCTYPE html>` から書き始めていない

---

## 🚀 このルールの適用方法

### 新規ファイル作成時
- **最初からルールに従う** - 既存コードを参考にせず、このドキュメントを参照

### 既存ファイル修正時
- **触る箇所だけ修正** - ファイル全体を一度に変更しない
- **PRのサイズに注意** - リファクタリングが大きくなる場合は別PRに分割

### チーム全体での運用
1. **週1回のレビュー会** - ルール違反のコードを共有して学習
2. **PRレビュー時にチェック** - このドキュメントのチェックリストを使用
3. **質問は積極的に** - ルールが不明な場合はチームで議論

---

## ❓ よくある質問（FAQ）

### Q1: 既存コードがルールに従っていない場合は？
**A:** 新規コードは必ずルールに従い、既存コードは「触るついでに修正」する方針です。
一度に全ファイルをリファクタリングすると、レビューが不可能になります。

### Q2: ルールに従うと開発速度が落ちませんか？
**A:** 最初の1-2週間は慣れるまで時間がかかりますが、慣れれば以下の理由で**開発速度が向上**します：
- コンフリクトが減る → マージ作業の時間削減
- レビューが早い → 承認待ち時間の削減
- バグが減る → デバッグ時間の削減

### Q3: 300行ルールでリファクタリングができません
**A:** リファクタリングは段階的に行ってください：
- Phase 1: 共通CSS抽出（150行）→ マージ
- Phase 2: BEM記法への変更（200行）→ マージ
- Phase 3: Blade構造変更（150行）→ マージ

### Q4: BEM記法がよく分かりません
**A:** まずは以下のパターンだけ覚えてください：
```css
.コンポーネント名 { }                    /* Block */
.コンポーネント名__部品名 { }            /* Element */
.コンポーネント名--状態 { }              /* Modifier */
```

例: `.card`, `.card__title`, `.card--highlighted`

### Q5: チーム全員がルールを守らない場合は？
**A:** 以下の対策を実施してください：
1. PRレビュー時にこのドキュメントを参照する
2. ルール違反のPRは「Changes Requested」でコメント
3. 週1回のレビュー会で違反例を共有（個人攻撃ではなく学習の場として）

---

## 📚 参考リンク

- [`.github/copilot-instructions.md`](../.github/copilot-instructions.md) - プロジェクト全体の技術仕様
- [Laravel公式ドキュメント](https://laravel.com/docs/12.x)
- [BEM記法公式サイト](http://getbem.com/)
- [Conventional Commits](https://www.conventionalcommits.org/) - コミットメッセージ規約

---

## 📝 改訂履歴

| 日付 | 変更内容 | 担当者 |
|------|---------|--------|
| 2025-11-23 | 初版作成 | - |

---

**このルールに関する質問・提案は、チームのSlack/Discord/チャットで共有してください。**
