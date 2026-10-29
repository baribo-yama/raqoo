# ADR-0002: `room_statuses` テーブルの扱い

- Status: Proposed
- Date: 2026-10-23
- Deciders: 未定
- 関連: [`../refactoring/rearchitecture-plan.md`](../refactoring/rearchitecture-plan.md)（設計上の要検討事項）、[`../refactoring/issues-to-fix-2026-10-23.md`](../refactoring/issues-to-fix-2026-10-23.md) の H-3

## Context（背景）

2026-10-23 時点のコードで確認できる事実：

- `room_statuses` を **書き込む** のは次の2か所だけ。
  - 管理者の承認（`Admin/ReservationController` の単体・一括承認）：`reserved` で `updateOrCreate`
  - 管理者 API `POST /admin/room-status`（画面からは使われていない）
- `room_statuses` を **読む** のは、ユーザーのホーム検索（`User/RoomController` の `whereDoesntHave('roomStatuses', …)`）だけ。検索で日付と開始・終了時限を指定すると、`available` 以外の行がある教室を除外する。
- 教室ごとの空き状況の表示（`/user/room-status`、`/admin/room-status`）は、`room_statuses` を使わず、`reservations` と `room_lessons` から計算している。
- ユーザーのキャンセル、管理者の却下、管理者による予約の削除では `room_statuses` を更新しない。このため承認済みの行が残る。
- `room_statuses.status` の DB 上の値は `available` / `reserved` / `in_use` / `unavailable` で、`pending` と、授業（`lesson`）に対応する値はない。
- `RoomStatus::getStatusForRoom` はどこからも呼ばれていない。

## Options（選択肢）

| 案 | 内容 | 良い点 | 悪い点・リスク |
|---|---|---|---|
| A | テーブルを廃止し、`reservations` と `room_lessons` から都度計算する | 真実源が1つになり、更新漏れが構造的になくなる | ホーム検索の絞り込み処理を作り直す必要がある。計算量が増えるため、必要ならキャッシュを検討 |
| B | 残して、予約の状態変更のたびに同期的に更新する | 既存の検索処理を維持できる | 更新する箇所（承認・キャンセル・却下・削除・授業登録など）をすべて網羅する必要がある。漏れると今回と同じ不整合が起きる |
| C | 残して、イベント経由で再構築される読み取り専用のプロジェクションにする | 書き込み側と読み取り側を分けられる | ドメインイベントと再構築の仕組みが必要で、現在の規模に対して構成が大きい |

## Decision（決定）

未決定。

## Consequences（結果）

未決定のため記載しない。

## 備考

- A を選ぶ場合、ホーム検索の「指定した日付・時限が空いている教室だけを残す」処理の置き換え方も合わせて決める必要がある。
