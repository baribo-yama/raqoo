# RoomController / Room モデル リファクタチェックリスト

対象:
- `raqoo/app/Http/Controllers/User/RoomController.php`
- `raqoo/app/Models/Room.php`
- `raqoo/resources/views/user/home.blade.php`

---

## 優先度凡例

| 記号 | 意味 |
|------|------|
| 🔴 | 即対応（本番に悪影響がある） |
| 🟠 | 早めに（バグ混入リスクがある） |
| 🟡 | 余裕があれば（品質改善） |

---

## 🔴 優先度1 — ステータス判定が常に `available` を返してしまっている

**場所:** `home.blade.php` L201

```php
// 現状（lessonMap, reservationMap を渡していない）
$status = $room->getStatusForPeriod($displayDate, $period, auth()->id());
```

`getStatusForPeriod()` の第4・第5引数 `$lessonMap` と `$reservationMap` が未渡しのため、
メソッド内部では空配列として扱われる。

`Room::getStatusForUser()` の判定ロジックはすべて `$lessonMap` / `$reservationMap` の参照に
依存しているため、授業中・他人の予約済みの部屋でも **常に `'available'` が返ってしまう。**

**修正方針:** `RoomController::index()` で `$lessonMap` と `$reservationMap` を事前構築し、
`compact()` でビューに渡す。

```php
// RoomController::index() に追加
use App\Models\RoomLesson;
use App\Models\Reservation;

$displayDate = $request->input('date', date('Y-m-d'));

$lessonMap = [];
foreach (RoomLesson::where('date', $displayDate)->get() as $lesson) {
    $lessonMap[$lesson->room_id][$lesson->period] = $lesson;
}

$reservationMap = [];
foreach (
    Reservation::where('date', $displayDate)
        ->whereIn('status', ['pending', 'reserved', 'in_use'])
        ->get() as $reservation
) {
    $reservationMap[$reservation->room_id][$reservation->period][] = $reservation;
}

return view('user.home', compact('rooms', 'date', 'startPeriod', 'endPeriod', 'people', 'floor', 'lessonMap', 'reservationMap'));
```

ビュー側の呼び出しも引数を追加する:

```php
// home.blade.php L201
$status = $room->getStatusForPeriod($displayDate, $period, auth()->id(), $lessonMap, $reservationMap);
```

参考実装: `RoomStatusController::getStatus()` L38-61 が正しいパターン。

---

## 🟠 優先度2 — N+1問題の潜在リスク

**場所:** `home.blade.php` L199-278（`@for` ループ内）

優先度1を「ビュー内でDBを直接叩く方法」で修正した場合、N+1が発生する。

教室10部屋 × 6時限 = 60回のループ内でDBを叩くと：

```
SELECT * FROM room_lessons  WHERE ... AND period=1  ← 60回
SELECT * FROM reservations  WHERE ... AND period=1  ← 60回
合計 120クエリ
```

**正しい修正:** 優先度1の方針通り、コントローラーで1回だけ取得して Map を渡す。
これにより DBクエリは 2回（lessonMap用 + reservationMap用）で済む。

---

## 🟡 優先度3 — `$lessonMap` / `$reservationMap` 構築ロジックの重複

**場所:**
- `RoomStatusController::getStatus()` L38-61
- `RoomController::index()`（優先度1修正後に追加予定）

同じ Map 構築ロジックが2箇所に存在することになる。

**修正案:** `Room` モデルや専用サービスクラスに切り出す。

```php
// 例: app/Services/RoomStatusService.php
class RoomStatusService
{
    public static function buildLessonMap(string $date): array { ... }
    public static function buildReservationMap(string $date): array { ... }
}
```

ただし、ロジックが安定してから着手すること。優先度1・2が完了後に検討。

---

## 🟡 優先度4 — ビュー内でのビジネスロジック実行

**場所:** `home.blade.php` L183-211（`@php` ブロック）

現状、ビュー内の `@php` でステータス計算・URLパラメータ組み立て・config参照を行っている。
ビューはあくまで表示に専念すべきであり、計算済みのデータをコントローラーから渡す構成が望ましい。

**修正案:** コントローラー側で各部屋・各時限のステータスを計算した結果を
`$roomStatuses` のような構造でビューに渡す。

優先度1〜3が完了後に着手する。

---

## 完了済み

なし
