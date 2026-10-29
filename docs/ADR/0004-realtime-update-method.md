# ADR-0004: ユーザー画面の自動更新方式

- Status: Proposed
- Date: 2026-10-23
- Deciders: 未定
- 関連: [`../refactoring/issues-to-fix-2026-10-23.md`](../refactoring/issues-to-fix-2026-10-23.md) の L-2、[`../spec/current-implementation-spec-2026-10-23.md`](../spec/current-implementation-spec-2026-10-23.md)

## Context（背景）

2026-10-23 時点のコードで確認できる事実：

- ユーザーのホーム画面は、日付を変更したときに `fetch('/user/room-status')` で空き状況を取得して表示を更新する（Ajax は導入済み）。
- 定期的な再取得や push はなく、他の人の予約や承認を反映するには、日付の選び直しか再読み込みが必要。
- 管理者ダッシュボードの承認待ちバッジだけ、30 秒ごとにポーリングしている。
- フロントエンドは素の JS（約 700 行）、Blade、JSON API で構成されている。Livewire は使っていない。
- キャッシュ（`Cache::`）はアプリ内で使われていない。キュー・セッションは DB ドライバ。

## Options（選択肢）

| 案 | 内容 | 良い点 | 悪い点・リスク |
|---|---|---|---|
| A | 既存の API を `setInterval` で定期取得する（ポーリング） | 既存コードへの追加が小さい | 更新は間隔ぶん遅れる。アクセスが増えるため、キャッシュ等の対策が必要になる場合がある |
| B | Livewire に移行する（`wire:poll` など） | PHP 側で状態を持つ構成にできる | 既存の JS・Blade・API の書き直しが必要になる |
| C | WebSocket（Laravel Reverb など）で push する | 承認の反映を即時にできる | 常駐プロセスが増え、Docker 構成と運用が重くなる |
| D | SSE（Server-Sent Events） | 片方向の push で実現できる | PHP-FPM では接続が長時間プロセスを占有する |

## Decision（決定）

未決定。

## Consequences（結果）

未決定のため記載しない。

## 備考

- 予約フォームの送信時に他の人に先に取られた場合の見せ方（リダイレクトか、JSON で再選択か）は、この判断とは別に検討する。
