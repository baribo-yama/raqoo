# ReservationController リファクタチェックリスト

対象: `raqoo/app/Http/Controllers/User/ReservationController.php`

---

## 優先度凡例

| 記号 | 意味 |
|------|------|
| 🔴 | 即対応（本番に悪影響がある） |
| 🟠 | 早めに（バグ混入リスクがある重複） |
| 🟡 | 余裕があれば（品質改善） |

---

## 🔴 優先度1 — デバッグコードの削除

**場所:** `create()` L116

```php
clock($period); // デバッグ
```

本番コードにデバッグ出力が残っている。即削除。

---

## 🔴 優先度2 — コメントアウトされたコードの削除

### A. 意図不明なコメントアウトブロック

**場所:** `create()` L149-160

```php
//-------以下のコードの意図がわからない------//
// $date = $validated['date'];
// ...
```

理解できないまま残っているコード。git 履歴に残るので削除してよい。

### B. 採用しなかった代替案

**場所:** `store()` L340-343

```php
// ↑の代替案　リダイレクト方法が異なる
// if ($conflict) {
//     return back()->withErrors([...
// }
```

採用しなかったコード。削除してよい。

---

## 🟠 優先度3 — `check()` と `store()` のバリデーションルール重複

**場所:** `check()` L194-201 / `store()` L265-273

完全に同じルールが2箇所に定義されている。片方を修正したときにもう片方を忘れるリスクがある。

**修正案:** プライベートメソッドに切り出す。

```php
private function reservationValidationRules(): array
{
    return [
        'room_id'   => 'required|exists:rooms,id',
        'date'      => 'required|date|after_or_equal:today',
        'periods'   => 'required|array|min:1|max:2',
        'periods.*' => 'integer|min:1|max:6',
        'purpose'   => 'required|string|max:100',
        'members'   => 'nullable|string',
    ];
}
```

呼び出し側:

```php
$validated = $request->validate($this->reservationValidationRules());
```

---

## 🟠 優先度4 — `store()` 内の `$conflict` リダイレクトで `period` の取り方が不正

**場所:** `store()` L332-338

```php
// $otherPersonReservation 側（正しい）
'period' => $firstPeriod,  // min($periods) — ソート済み配列から最小値

// $conflict 側（問題あり）
'period' => $validated['periods'][0] ?? null,  // ソートされていない配列の先頭
```

`$validated['periods']` はソートされていないため、ユーザーが `[3, 2]` の順で送信したとき `period=3` でリダイレクトされてしまう。

**修正案:**

```php
if ($conflict) {
    return redirect()->route('user.reservations.create', [
        'room_id' => $validated['room_id'],
        'date'    => $validated['date'],
        'period'  => min($periods),
    ])->withErrors(['error' => '同時刻にすでにあなたの予約があるため予約できません']);
}
```

---

## 🟡 優先度5 — `hasEndedPeriod()` のシグネチャ改善

**場所:** `hasEndedPeriod()` L29 / `create()` L118

`create()` だけ単一の `$period` をわざわざ配列に包んで渡している。

```php
// 現状
if ($period && $this->hasEndedPeriod($request->date, [$period])) {
```

**修正案:** シグネチャを `int|array` 対応にする。

```php
private function hasEndedPeriod(string $date, int|array $periods): bool
{
    // ...
    foreach ((array) $periods as $period) {
```

呼び出し側がシンプルになる:

```php
if ($period && $this->hasEndedPeriod($request->date, $period)) {
```

---

## 🟡 優先度6 — `create()` の肥大化

**場所:** `create()` L79-187（約110行）

1つのメソッドが以下の5つの責務を持っている。

1. 今日の予約ブロック（終了時刻まで新規予約禁止）
2. URL直叩きブロック（終了済み時限の弾き）
3. 明日以降の件数制限（1件まで）
4. 2週間先制限
5. 予約・授業データの取得とビュー表示

ロジックの正確な理解とテスト整備が必要なため、他の項目が完了してから着手する。

---

## 完了済み

| 項目 | 対応内容 |
|------|--------|
| 連続時限チェックのメソッド化 | `isConsecutivePeriods()` として切り出し済み |
