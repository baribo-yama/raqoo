# ADR-0003: Repository パターンを導入するか

- Status: Proposed
- Date: 2026-10-23
- Deciders: 未定
- 関連: [`../refactoring/rearchitecture-plan.md`](../refactoring/rearchitecture-plan.md)（ステップ 4・6、設計上の要検討事項）

## Context（背景）

2026-10-23 時点の事実：

- コントローラーが Eloquent モデルを直接呼び出し、予約可否の判定・状態遷移・通知・メール送信までを直接行っている。
- `rearchitecture-plan.md` は段階的な移行を示しており、UseCase 層の導入（ステップ 4）までと、Repository パターンの導入（ステップ 6、任意）を分けている。
- 同じドキュメントに「プロジェクト規模に対して過剰設計になるリスクを踏まえて判断する」と書かれている。
- 自動テストは、Breeze 標準のテストと `TimeGuardMiddlewareTest` が中心で、予約まわりのテストはほぼない。

## Options（選択肢）

| 案 | 内容 | 良い点 | 悪い点・リスク |
|---|---|---|---|
| A | UseCase 層を導入し、Eloquent は直接使う | 変更量が小さい。Laravel の標準的な書き方のまま、コントローラーを薄くできる | ドメインロジックが Eloquent に依存したまま残る |
| B | UseCase 層に加えて、Repository（インターフェース＋Eloquent 実装）を導入する | ドメインを永続化から切り離せ、テストで差し替えやすい | クラス数が増え、現在の規模に対して構成が重くなる可能性がある |

## Decision（決定）

未決定。

## Consequences（結果）

未決定のため記載しない。
