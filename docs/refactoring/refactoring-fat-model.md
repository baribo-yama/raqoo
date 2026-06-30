# Fat Model / Skinny Controller リファクタリング方針
作成日：2026-0531

## 目的

このドキュメントは、現在のコードベースを **Fat Model / Skinny Controller** の思想に沿って整理するための方針と根拠をまとめたものです。
現状の問題点を具体的なファイル・行番号付きで示し、どう直すべきかを記述しています。

---

## Fat Model / Skinny Controller とは

| 役割 | 責務 |
|------|------|
| **Controller** | リクエストの受け取り → モデルへの委譲 → レスポンスの返却（3ステップのみ） |
| **Model** | ビジネスルール・クエリ・バリデーション・算出プロパティ |

コントローラーは「何をするか」を決め、実際の処理はモデルが持つ。これにより、ロジックが一箇所に集まり、重複・テスト困難・メンテナンス困難を防ぐ。

---

## 現状の問題点（コードベース調査結果）

### 問題1: ビジネスルールがコントローラーに直書きされている

**該当箇所:**
- `app/Http/Controllers/User/ReservationController.php` `create()` メソッド（35〜74行目）

**現在のコード（抜粋）:**
```php
// ReservationController::create() の中
$todayReservations = Reservation::where('user_id', Auth::id())
    ->whereDate('date', today())
    ->whereIn('status', ['pending', 'reserved', 'in_use'])
    ->get();

foreach ($todayReservations as $reservation) {
    $endTimeConfig = config("period.{$reservation->period}.end_time");
    if (!$endTimeConfig) { continue; }
    $endTime = Carbon::parse($endTimeConfig);
    if (Carbon::parse($request->date)->isSameDay(Carbon::today())) {
        if (now()->lessThan($endTime)) {
            return redirect()->route('user.home.index')
                ->with('error', '今日の予約の利用が終了してから新たに予約してください');
        }
    }
}

if (Carbon::parse($request->date)->greaterThan(Carbon::today())) {
    $isExist = Reservation::where('user_id', Auth::id())
        ->where('date', $date)
        ->whereIn('status', ['pending', 'reserved', 'in_use'])
        ->exists();
    if ($isExist) {
        return redirect()->route('user.home.index')
            ->with('error', '1件すでに予約が入っているのでできません');
    }
}
```

**問題点:**
- 「今日の予約が終了するまで次の予約は不可」「明日以降は1日1件まで」というドメインルールがコントローラー内にある
- コントローラーが「ビジネスルールの判定」を担っており、責務が混在している
- 同じルールを他の画面（`check()`, `store()`）でも使いたい場合に重複する

**あるべき姿:**
```php
// Reservation モデルに移動
public static function blocksTodayNewBooking(int $userId, string $date): bool { ... }
public static function futureDateLimitReached(int $userId, string $date): bool { ... }

// コントローラーは判定結果を使うだけ
if (Reservation::blocksTodayNewBooking(Auth::id(), $request->date)) {
    return redirect()->route('user.home.index')
        ->with('error', '今日の予約の利用が終了してから新たに予約してください');
}
```

---

### 問題2: 重複チェックロジックが複数箇所に散在している（DRY違反）

**該当箇所:**
- `app/Http/Controllers/User/ReservationController.php` `check()` メソッド（139〜155行目）
- `app/Http/Controllers/User/ReservationController.php` `store()` メソッド（213〜234行目）

**現在のコード（`check()` と `store()` の両方に存在する）:**
```php
$existing = Reservation::where('room_id', $validated['room_id'])
    ->where('date', $validated['date'])
    ->whereIn('period', $validated['periods'])
    ->where('status', '!=', 'cancelled')
    ->exists();

$existingLesson = RoomLesson::where('room_id', $validated['room_id'])
    ->where('date', $validated['date'])
    ->whereIn('period', $validated['periods'])
    ->exists();
```

また、連続時限チェックも `check()`（160〜167行目）と `store()`（202〜209行目）に全く同じコードが重複している。

**問題点:**
- 同じクエリロジックが2箇所に書かれており、片方を修正するともう一方が古くなるリスクがある
- ロジックの意図（「重複予約の防止」）がコントローラーに埋まっており、モデルを見ても分からない

**あるべき姿:**
```php
// Reservation モデルに移動
public static function hasConflict(int $roomId, string $date, array $periods): bool { ... }
public static function hasPeriodsConflict(int $userId, string $date, array $periods, int $exceptRoomId): bool { ... }

// RoomLesson モデルに移動
public static function blocksReservation(int $roomId, string $date, array $periods): bool { ... }

// コントローラーは組み合わせるだけ
if (Reservation::hasConflict(...) || RoomLesson::blocksReservation(...)) { ... }
```

---

### 問題3: グルーピングロジックが2つのコントローラーに重複している

**該当箇所:**
- `app/Http/Controllers/Admin/DashBoardController.php` `groupReservations()` / `formatGroup()`（48〜117行目）
- `app/Http/Controllers/Admin/ReservationController.php` `count()` メソッド（180〜222行目）

**問題点:**
- 「連続時限の予約を1グループとして扱う」ロジックが2つのコントローラーにほぼ同じ実装で存在する
- `DashBoardController` に `private` メソッドとして書かれているため、`ReservationController` から流用できず、結果として重複が生まれた
- ロジックの変更（グルーピング条件の修正など）が2箇所の変更を要求する

**あるべき姿:**
```php
// Reservation モデルに移動
public static function groupByBooking($reservations): array { ... }

// 両コントローラーから呼ぶだけ
$reservations = Reservation::groupByBooking($rawReservations);
```

---

### 問題4: `lessonMap` / `reservationMap` の構築が2つのコントローラーに重複している

**該当箇所:**
- `app/Http/Controllers/Admin/RoomStatusController.php` `getStatus()` メソッド（68〜77行目）
- `app/Http/Controllers/User/RoomStatusController.php` `getStatus()` メソッド（36〜58行目）

**現在のコード（両方に同じ構造が存在）:**
```php
$lessonMap = [];
foreach (RoomLesson::where('date', $date)->get() as $lesson) {
    $lessonMap[$lesson->room_id][$lesson->period] = $lesson;
}

$reservationMap = [];
foreach (Reservation::where('date', $date)
    ->whereIn('status', ['pending', 'reserved', 'in_use'])
    ->get() as $reservation) {
    $reservationMap[$reservation->room_id][$reservation->period][] = $reservation;
}
```

**あるべき姿:**
```php
// RoomLesson モデルに移動
public static function buildMapForDate(string $date): array { ... }

// Reservation モデルに移動
public static function buildActiveMapForDate(string $date): array { ... }
```

---

### 問題5: `Reservation` モデルが薄すぎる

**該当箇所:** `app/Models/Reservation.php`

現在の `Reservation` モデルはリレーション2つとアクセサ1つのみで、コアエンティティであるにもかかわらずビジネスロジックが皆無。問題1〜4で列挙したすべての処理がコントローラーに流れ込んでいる原因でもある。

また、頻繁に使われる絞り込み条件（`status = pending` など）がクエリスコープとして定義されておらず、各コントローラーに生クエリが散在している。

**あるべき姿（スコープの例）:**
```php
// Reservation モデル
public function scopePending($query)   { return $query->whereIn('status', ['pending']); }
public function scopeActive($query)    { return $query->whereIn('status', ['pending', 'reserved', 'in_use']); }
public function scopeForUser($query, int $userId) { return $query->where('user_id', $userId); }
```

---

### 問題6: `User` モデルに `reservations` リレーションがない

**該当箇所:** `app/Models/User.php`

`hasMany(Reservation::class)` が定義されていないため、「ユーザーの予約を取得する」処理がすべて `Reservation::where('user_id', Auth::id())` という生クエリで書かれている。

---

### 問題7: ステータス文字列がマジックストリングのまま

**該当箇所:** 複数のコントローラー・モデル全体

`'pending'`, `'reserved'`, `'in_use'`, `'cancelled'` といった文字列がコードベース全体に直書きされており、タイポリスクとIDEサポート不足がある。

**あるべき姿:**
```php
// app/Enums/ReservationStatus.php
enum ReservationStatus: string
{
    case Pending   = 'pending';
    case Reserved  = 'reserved';
    case InUse     = 'in_use';
    case Cancelled = 'cancelled';
    case Finished  = 'finished';
}
```

---

## リファクタリング優先度と作業順序

### Phase 1（高優先度）: 重複クエリの排除とモデルへの移動

| 作業 | 移動元 | 移動先 |
|------|--------|--------|
| 重複チェックメソッド追加 | `User/ReservationController::check()`, `store()` | `Reservation::hasConflict()` |
| 連続時限チェックメソッド追加 | 同上 | `Reservation::areConsecutive()` |
| 今日ブロックルール | `User/ReservationController::create()` | `Reservation::blocksTodayNewBooking()` |
| 翌日以降1件ルール | 同上 | `Reservation::futureDateLimitReached()` |

**ブランチ名例:** `refactor/reservation-move-rules-to-model`

---

### Phase 2（高優先度）: クエリスコープの追加と生クエリの置き換え

| 作業 | 内容 |
|------|------|
| `scopeActive()` 追加 | `status IN (pending, reserved, in_use)` を共通化 |
| `scopePending()` 追加 | `status = pending` を共通化 |
| `scopeForUser()` 追加 | `user_id = ?` を共通化 |
| `User::reservations()` リレーション追加 | |

**ブランチ名例:** `refactor/reservation-add-scopes`

---

### Phase 3（中優先度）: グルーピングロジックの統一

| 作業 | 移動元 | 移動先 |
|------|--------|--------|
| グルーピングロジック統一 | `DashBoardController`, `Admin/ReservationController` | `Reservation::groupByBooking()` |
| map構築メソッド追加 | `Admin/RoomStatusController`, `User/RoomStatusController` | `RoomLesson::buildMapForDate()`, `Reservation::buildActiveMapForDate()` |

**ブランチ名例:** `refactor/reservation-grouping-to-model`

---

### Phase 4（低優先度）: Enum 導入

| 作業 | 内容 |
|------|------|
| `ReservationStatus` Enum 作成 | `app/Enums/ReservationStatus.php` |
| 全コード置き換え | マジックストリングを Enum 定数に変更 |

**ブランチ名例:** `refactor/reservation-status-enum`

---

## 各フェーズでの注意点

- 各 Phase は独立した PR にすること（`development-rules.md` の300行ルールを遵守）
- ロジックをモデルに移動した後、コントローラー側が正しく委譲できているかを動作確認する
- Phase 1 完了後、`check()` と `store()` の重複が解消されていることを grep で確認する

```bash
# 重複クエリが残っていないか確認
grep -n "whereIn('period'" app/Http/Controllers/User/ReservationController.php
```

---

## 参考

- [リファクタリング評価基準](./refactoring-checklist.md)
- [開発ルール](./development-rules.md)

---

| 日付 | 変更内容 |
|------|---------|
| 2026-05-31 | 初版作成（コードベース調査結果をもとに作成） |
