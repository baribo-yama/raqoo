# リファクタリング評価基準

## 📌 目的

このドキュメントは、コードをリファクタリングする際の**評価基準**をまとめたものです。
コードレビュー時や、自分のコードを見直す際のチェックリストとして活用してください。

---

## ✅ リファクタリング前のチェックリスト

リファクタリングを始める前に、以下を確認してください：

- [ ] 既存の機能をテストで確認（または動作確認）している
- [ ] 影響範囲を把握している（どのファイルが関連するか）
- [ ] 変更理由が明確（なぜリファクタリングが必要か）
- [ ] 1つの PR で変更する行数が 300行以内

---

## 🎯 評価基準（10項目）

### 1. DRY原則（Don't Repeat Yourself）

**評価ポイント:**
- [ ] 同じコードが2箇所以上に存在しない
- [ ] 重複するロジックが共通メソッド化されている
- [ ] 定数や設定値が共通の場所で管理されている

**チェック方法:**
```bash
# 重複コードを検索
grep -rn "preg_split('/[\n\r]+/'" app/
```

**悪い例:**
```php
// User/ReservationController.php
$members = preg_split('/[\n\r]+/', trim($request->members));
$memberCount = 1 + count(array_filter($members, 'trim'));

// Admin/ReservationController.php
$members = preg_split('/[\n\r]+/', trim($request->members));  // 重複！
$memberCount = 1 + count(array_filter($members, 'trim'));
```

**良い例:**
```php
// Reservation モデルに共通メソッド
public static function calculateMemberCount(string $members): int
{
    $memberLines = preg_split('/[\n\r]+/', trim($members));
    return 1 + count(array_filter($memberLines, 'trim'));
}

// Controller では呼び出すだけ
$memberCount = Reservation::calculateMemberCount($request->members);
```

**スコア:**
- 🟢 重複なし: 5点
- 🟡 一部重複あり: 3点
- 🔴 重複が多い: 0点

---

### 2. KISS原則（Keep It Simple, Stupid）

**評価ポイント:**
- [ ] 1つのメソッドが30行以内
- [ ] 複雑な条件分岐が3段階以内
- [ ] 誰が読んでも理解できるシンプルなコード

**チェック方法:**
- メソッドの行数を数える
- ネストの深さを確認（`if` の中に `if` の中に `if`...）

**悪い例:**
```php
public function getStatusBadge()
{
    return match(true) {
        $this->status === 'pending' && $this->created_at->diffInHours(now()) > 24 => 'warning',
        $this->status === 'approved' && $this->date < now()->toDateString() => 'past',
        $this->status === 'in_use' && $this->period === now()->hour => 'active',
        default => $this->status
    };  // 複雑すぎて意図が不明
}
```

**良い例:**
```php
public function getStatusLabel(): string
{
    return match($this->status) {
        'pending' => '承認待ち',
        'approved' => '承認済み',
        'in_use' => '利用中',
        'finished' => '完了',
        'rejected' => '却下',
        'cancelled' => 'キャンセル',
        default => '不明',
    };  // シンプルで分かりやすい
}
```

**スコア:**
- 🟢 すべてのメソッドが30行以内、ネスト3段階以内: 5点
- 🟡 一部長いメソッドあり: 3点
- 🔴 複雑で理解困難: 0点

---

### 3. 早期リターン（Early Return）

**評価ポイント:**
- [ ] エラー条件を先にチェックして早期 return
- [ ] 正常処理のネストが2段階以内
- [ ] コードが左寄せされている

**チェック方法:**
- `if` のネストを確認
- 正常処理が深い位置にあるかチェック

**悪い例:**
```php
public function store(Request $request)
{
    if ($request->has('room_id')) {
        $room = Room::find($request->room_id);
        if ($room) {
            if ($room->capacity >= $request->member_count) {
                // 正常処理がネスト4段階目（読みづらい）
                $reservation = Reservation::create([...]);
                return redirect()->route(...);
            } else {
                return back()->withErrors(['error' => '人数オーバー']);
            }
        } else {
            return back()->withErrors(['error' => '教室が見つかりません']);
        }
    } else {
        return back()->withErrors(['error' => '教室IDがありません']);
    }
}
```

**良い例:**
```php
public function store(Request $request)
{
    // エラーは早めに return
    if (!$request->has('room_id')) {
        return back()->withErrors(['error' => '教室IDがありません']);
    }

    $room = Room::find($request->room_id);
    if (!$room) {
        return back()->withErrors(['error' => '教室が見つかりません']);
    }

    if ($room->capacity < $request->member_count) {
        return back()->withErrors(['error' => '人数オーバー']);
    }

    // 正常処理は左寄せ（読みやすい）
    $reservation = Reservation::create([...]);
    return redirect()->route('user.reservations.index');
}
```

**スコア:**
- 🟢 すべて早期リターン、ネスト2段階以内: 5点
- 🟡 一部深いネストあり: 3点
- 🔴 深いネストが多い: 0点

---

### 4. マジックナンバー/マジックストリングの排除

**評価ポイント:**
- [ ] 数値リテラルが定数化されている
- [ ] 文字列リテラル（ステータスなど）が Enum または定数化されている
- [ ] 意味が明確な命名

**チェック方法:**
```bash
# ステータス文字列の直接使用を検索
grep -rn "=== 'pending'" app/
grep -rn "=== 'approved'" app/
```

**悪い例:**
```php
if ($request->period > 6) {  // 6 の意味が不明
    return back()->withErrors(['error' => '時間帯が不正です']);
}

if ($reservation->status === 'pending') { }  // タイポリスク
if ($reservation->status === 'pneding') { }  // タイポバグ！
```

**良い例:**
```php
// Reservation モデル
class Reservation extends Model
{
    public const MAX_PERIOD = 6;  // 最大時間帯
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
}

// Controller
if ($request->period > Reservation::MAX_PERIOD) {
    return back()->withErrors(['error' => '時間帯が不正です']);
}

if ($reservation->status === Reservation::STATUS_PENDING) {
    // IDE の補完が効く
}
```

**さらに良い例（Enum 使用）:**
```php
// app/Enums/ReservationStatus.php
enum ReservationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}

// 使用時
if ($reservation->status === ReservationStatus::PENDING->value) {
    // タイポ不可能！
}
```

**スコア:**
- 🟢 すべて定数化または Enum 化: 5点
- 🟡 一部マジックナンバー/ストリングあり: 3点
- 🔴 多数の直接記述: 0点

---

### 5. トランザクションの適切な使用

**評価ポイント:**
- [ ] 複数の DB 操作を含むメソッドで `DB::transaction()` を使用
- [ ] エラー時に自動ロールバック
- [ ] ログ出力が適切

**チェック方法:**
- `create()`, `update()`, `delete()` が複数あるメソッドを確認
- `DB::beginTransaction()` と `DB::commit()` の対応をチェック

**悪い例:**
```php
public function store(Request $request)
{
    // トランザクションなし
    $reservation = Reservation::create([...]);  // 成功
    Notification::create([...]);  // エラー発生！

    // 結果: 予約だけ作られて通知が作られない（データ不整合）
}
```

**良い例:**
```php
public function store(Request $request)
{
    DB::beginTransaction();

    try {
        $reservation = Reservation::create([...]);
        Notification::create([...]);

        DB::commit();

        return redirect()->route('user.reservations.index');

    } catch (\Exception $e) {
        DB::rollBack();

        Log::error('予約作成エラー', [
            'user_id' => auth()->id(),
            'error' => $e->getMessage()
        ]);

        return back()->withErrors(['error' => '予約の申請に失敗しました']);
    }
}
```

**スコア:**
- 🟢 複数DB操作すべてでトランザクション使用: 5点
- 🟡 一部トランザクションなし: 3点
- 🔴 トランザクション未使用: 0点

---

### 6. N+1 問題の回避（Eager Loading）

**評価ポイント:**
- [ ] `with()` でリレーションを事前ロード
- [ ] ループ内で追加クエリが発生しない
- [ ] Laravel Debugbar でクエリ数を確認済み

**チェック方法:**
```bash
# Laravel Debugbar をインストール（開発環境のみ）
composer require barryvdh/laravel-debugbar --dev
```

画面下部のクエリ数を確認（100件表示で 201回クエリなら N+1 問題）。

**悪い例:**
```php
// Controller
$reservations = Reservation::all();  // 1回目のクエリ

// Blade
@foreach($reservations as $reservation)
    <p>{{ $reservation->room->name }}</p>     // 各ループで1回クエリ
    <p>{{ $reservation->user->name }}</p>     // 各ループで1回クエリ
@endforeach

// 結果: 100件なら 1 + 100 + 100 = 201回クエリ
```

**良い例:**
```php
// Controller
$reservations = Reservation::with(['room', 'user'])->get();  // 3回だけ

// Blade（同じコード）
@foreach($reservations as $reservation)
    <p>{{ $reservation->room->name }}</p>     // 追加クエリなし
    <p>{{ $reservation->user->name }}</p>     // 追加クエリなし
@endforeach

// 結果: 3回のクエリで完了
```

**スコア:**
- 🟢 すべてのリレーションで Eager Loading 使用: 5点
- 🟡 一部 N+1 問題あり: 3点
- 🔴 N+1 問題が多数: 0点

---

### 7. 単一責任の原則（SRP）

**評価ポイント:**
- [ ] 1つのメソッドが1つの仕事だけをする
- [ ] メソッド名から機能が明確
- [ ] コントローラーが20-30行以内

**チェック方法:**
- メソッド内の処理を箇条書きにする
- 「〜して、〜して、〜する」と「して」が3つ以上なら分割検討

**悪い例:**
```php
// 1つのメソッドが7つの仕事をしている
public function store(Request $request)
{
    // 1. バリデーション
    $validated = $request->validate([...]);

    // 2. 重複チェック
    if (Reservation::isDuplicate(...)) { }

    // 3. トランザクション開始
    DB::beginTransaction();

    // 4. 予約作成
    $reservation = Reservation::create([...]);

    // 5. 通知作成
    Notification::create([...]);

    // 6. メール送信
    Mail::to($admin)->send(new ReservationNotification(...));

    // 7. ログ出力
    Log::info('予約作成', [...]);
}
```

**良い例:**
```php
// Controller（リクエスト受付だけ）
public function store(StoreReservationRequest $request)
{
    $reservation = Reservation::createWithNotification(
        auth()->id(),
        $request->validated()
    );

    return redirect()->route('user.reservations.index')
        ->with('success', '予約を申請しました');
}

// Model（予約作成の責任）
public static function createWithNotification(int $userId, array $data): self
{
    // 重複チェック、作成、通知を内部で処理
}
```

**スコア:**
- 🟢 すべてのメソッドが単一責任: 5点
- 🟡 一部複数責任あり: 3点
- 🔴 多くのメソッドが複数責任: 0点

---

### 8. YAGNI原則（You Aren't Gonna Need It）

**評価ポイント:**
- [ ] 今使われていないメソッドが存在しない
- [ ] 「将来使うかも」で作られたコードがない
- [ ] 必要最小限の機能だけが実装されている

**チェック方法:**
```bash
# 未使用メソッドを検索（例: assignToRoom メソッド）
grep -rn "assignToRoom" app/ resources/
```

**悪い例:**
```php
// Reservation モデル
public function assignToRoom(Room $room) { }        // 使われていない
public function transferToUser(User $user) { }      // 使われていない
public function archive() { }                       // 使われていない
public function exportToPDF() { }                   // 将来必要かも？（今は不要）
```

**良い例:**
```php
// 実際に使われているメソッドだけ
public function approve() { }      // 管理者の承認処理
public function reject($reason) { } // 管理者の却下処理
public function cancel() { }        // ユーザーのキャンセル
```

**スコア:**
- 🟢 未使用メソッドなし: 5点
- 🟡 一部未使用あり: 3点
- 🔴 多数の未使用メソッド: 0点

---

### 9. 依存性注入（DI）

**評価ポイント:**
- [ ] クラス内部で `new` を使っていない
- [ ] コンストラクタまたはメソッドで依存を注入
- [ ] テスト時にモックと差し替え可能

**チェック方法:**
```bash
# Controller 内の new を検索
grep -rn "new .*Service" app/Http/Controllers/
```

**悪い例:**
```php
public function store(Request $request)
{
    $service = new ReservationService();  // 内部で new
    $reservation = $service->createReservation(...);
}
```

**良い例:**
```php
// Laravel のサービスコンテナが自動解決
public function store(Request $request, ReservationService $service)
{
    $reservation = $service->createReservation(...);
}
```

**スコア:**
- 🟢 すべて DI で依存を注入: 5点
- 🟡 一部 `new` を使用: 3点
- 🔴 多数の `new` を使用: 0点

---

### 10. セキュリティの基本

**評価ポイント:**
- [ ] ユーザー入力を必ずバリデーション
- [ ] Blade で `{{ }}` を使用（自動エスケープ）
- [ ] フォームに `@csrf` トークン
- [ ] Eloquent ORM を使用（生SQLを避ける）

**チェック方法:**
```bash
# 危険なパターンを検索
grep -rn "DB::select.*\$" app/     # 生SQLとユーザー入力の結合
grep -rn "{!! \$" resources/views/  # エスケープなし出力
grep -rn "<form" resources/views/ | grep -v "@csrf"  # CSRF なしフォーム
```

**悪い例:**
```php
// SQLインジェクションの危険
$reservation = DB::select("SELECT * FROM reservations WHERE id = " . $request->id);

// XSSの危険
{!! $reservation->purpose !!}  // エスケープなし

// CSRFトークンなし
<form method="POST" action="...">
    <!-- @csrf がない -->
</form>
```

**良い例:**
```php
// Eloquent を使う（自動でプリペアードステートメント）
$reservation = Reservation::find($request->id);

// Blade で自動エスケープ
{{ $reservation->purpose }}

// CSRF トークン
<form method="POST" action="...">
    @csrf
</form>
```

**スコア:**
- 🟢 すべてのセキュリティ対策が適用済み: 5点
- 🟡 一部対策漏れあり: 3点
- 🔴 危険な実装あり: 0点（即修正）

---

## 📊 総合評価

### スコア計算

| 項目 | 配点 | 獲得点 |
|------|------|--------|
| 1. DRY原則 | 5点 | |
| 2. KISS原則 | 5点 | |
| 3. 早期リターン | 5点 | |
| 4. マジックナンバー排除 | 5点 | |
| 5. トランザクション | 5点 | |
| 6. N+1問題回避 | 5点 | |
| 7. 単一責任の原則 | 5点 | |
| 8. YAGNI原則 | 5点 | |
| 9. 依存性注入 | 5点 | |
| 10. セキュリティ | 5点 | |
| **合計** | **50点** | |

### 評価基準

- **45-50点:** 🟢 優秀（マージ推奨）
- **35-44点:** 🟡 良好（軽微な修正でマージ可）
- **25-34点:** 🟠 要改善（リファクタリング推奨）
- **0-24点:** 🔴 不合格（大幅な修正が必要）

---

## 🔄 リファクタリング後のチェックリスト

リファクタリング完了後、以下を確認してください：

- [ ] すべての既存機能が正常に動作する
- [ ] 上記10項目のスコアが改善している
- [ ] コードレビューでチームメンバーが理解できる
- [ ] PR のサイズが 300行以内
- [ ] 変更理由が明確に記載されている

---

## 📚 参考ドキュメント

- [開発ルール](./development-rules.md) - Git運用、命名規則など
- [データベースセットアップ](./database-setup.md) - マイグレーション手順
- [技術仕様](../.github/copilot-instructions.md) - プロジェクト全体の設計

---

## 📝 改訂履歴

| 日付 | 変更内容 | 担当者 |
|------|---------|--------|
| 2025-11-23 | 初版作成 | - |

---

**このドキュメントを使ってリファクタリングの質を向上させましょう！**
