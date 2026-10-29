# ADR-0001: 管理者の却下とユーザーのキャンセルを別ステータスにするか

- Status: Proposed
- Date: 2026-10-23
- Deciders: 未定
- 関連: [`../refactoring/rearchitecture-plan.md`](../refactoring/rearchitecture-plan.md)（設計上の要検討事項）、[`../refactoring/issues-to-fix-2026-10-23.md`](../refactoring/issues-to-fix-2026-10-23.md) の H-2

## Context（背景）

2026-10-23 時点のコードで確認できる事実：

- 管理者による却下（`Admin/ReservationController::reject` / `bulkReject`）と、ユーザー自身のキャンセル（`User/ReservationController::cancel`）は、どちらも `reservations.status = 'cancelled'` になる。履歴上は区別できない。
- `reservations.status` の DB 上の値は `pending` / `reserved` / `in_use` / `cancelled` / `finished` で、`rejected` は存在しない。
- 却下理由を保存する `rejection_reason` 列はないが、`Reservation::$fillable` と2つの画面（予約詳細・利用履歴）はこの列と `rejected` を参照している。
- 管理者は却下理由を入力する画面を持たない。
- 画面の一部（予約一覧・予約詳細・利用履歴）は `rejected` を前提にバッジを分岐している。

## Options（選択肢）

| 案 | 内容 | 良い点 | 悪い点・リスク |
|---|---|---|---|
| A | `rejected` を別ステータスとして追加する | 履歴で却下とキャンセルを区別できる。既存の画面の分岐が意図どおり動く | ステータスの追加（マイグレーション、`cancelled` のみを前提にした箇所の見直し）が必要 |
| B | `cancelled` に統一したまま、却下かどうかを別の列（理由・実行者など）で表す | ステータスの種類は増えない | 「却下」を判定する条件が列の組み合わせになり、画面側の分岐を作り直す必要がある |

## Decision（決定）

未決定。

## Consequences（結果）

未決定のため記載しない。

## 備考

- 却下理由の入力を実装するかどうかも、この判断と合わせて決める必要がある（現状は入力欄がない）。
