# DDD観点でのリアーキテクチャ方針

作成日：2026-10-09

## 目的

本ドキュメントは、教室予約システム raqoo を DDD（ドメイン駆動設計）の観点で段階的にリアーキテクチャするための方針をまとめたものです。

既存の [`refactoring-fat-model.md`](./refactoring-fat-model.md) は「Fat Model / Skinny Controller」への移行（Eloquent モデルへのロジック集約）を目的としていますが、本ドキュメントはその先にある「ドメインロジックをフレームワーク（Eloquent）から独立させる」という、より進んだ方向性を示すものです。両者は対立するものではなく、Phase 1〜2（Fat Model 化）を土台にして、Phase 3 以降で DDD レイヤーへ発展させる関係にあります。

## 現状分析

### アーキテクチャ上の問題点

**Fat Controller 化**

- `User/ReservationController`：予約可否判定（終了時限チェック、連続時限チェック、1日1予約ルール、2週間先までルール、重複チェック）が直書きされている
- `Admin/ReservationController`：承認・却下処理に加え、通知生成とメール送信までを直接オーケストレーションしている
- `Admin/RoomStatusController` と `User/RoomStatusController`：教室の空き状況計算ロジック（`lessonMap` / `reservationMap` の構築パターン）がほぼ同一のまま2箇所に重複実装されている

**ドメインルールが単一の場所に集約されていない**

- 営業時間判定 → `TimeGuardMiddleware`
- 時限時刻の定義 → `config/period.php`
- 2週間先ルール・1日1予約ルール・連続時限ルール → 各コントローラーに直書き
- 教室の空き状態計算 → `Room::getStatusForPeriod`（モデル）と Admin/User 両 `RoomStatusController`（コントローラー）に分散

**データ整合性のリスク**

- `Reservation.status`（真実源）と `RoomStatus` テーブル（キャッシュ投影）の二重管理になっている
- 承認時は `RoomStatus::updateOrCreate` で更新されるが、ユーザー側の `cancel()` では `RoomStatus` が更新されず、不整合が発生し得る状態になっている
- 管理者による却下（`reject`）とユーザーの自己キャンセル（`cancel`）が同じ `cancelled` ステータスに統合されており、意味的に区別できない

**Anemic Domain Model**

- `Reservation` / `RoomStatus` / `RoomLesson` はリレーションとアクセサのみでビジネスロジックを持たない
- `Room` のみ一部ロジックを持つが、`private` メソッドで隠蔽されているだけでドメインサービスとして独立していない

**マジックストリング**

- `pending` / `reserved` / `cancelled` / `in_use` / `finished` / `unavailable` / `available` / `conflict` / `lesson` といった文字列が各所に裸で出現している

## 提案アーキテクチャ

```
app/
  Domain/
    Reservation/
      Reservation.php          … 集約ルート。approve()/reject()/cancel()/start()/finish() で状態遷移をカプセル化
      ReservationStatus.php    … PHP8.1 enum（Pending / Reserved / Rejected / Cancelled / InUse / Finished）
      ReservationPolicy.php    … ドメインサービス。営業時間・2週間ルール・1日1予約・連続時限・重複チェックを統合
      Period.php               … 値オブジェクト（時限番号＋開始/終了時刻。config/period.php をラップ）
      ReservationRepositoryInterface.php
      Events/
        ReservationRequested.php
        ReservationApproved.php
        ReservationRejected.php
        ReservationCancelled.php
    Room/
      Room.php                 … 集約ルート
      RoomSchedule.php         … Entity/サービス。Lesson と Reservation から当日の全時限ステータスを1箇所で計算する（現状3箇所に重複している計算ロジックの統合先）
      Lesson.php
      RoomAvailabilityStatus.php … enum
      RoomRepositoryInterface.php
    Notification/
      Notification.php
      NotificationRepositoryInterface.php

  Application/                 … UseCase 層。トランザクション境界とドメインイベント発行はここに置く
    Reservation/
      CreateReservationUseCase.php
      ApproveReservationUseCase.php
      BulkApproveReservationsUseCase.php
      RejectReservationUseCase.php
      CancelReservationUseCase.php
      GetRoomStatusUseCase.php      … Admin / User で共通化
    Listeners/
      SendReservationApprovedMail.php
      SendReservationRejectedMail.php   … ドメインイベントを listen してメール送信と Notification 作成を行う

  Infrastructure/
    Persistence/Eloquent/
      EloquentReservationRepository.php
      EloquentRoomRepository.php
    Mail/                       … 既存 Mail クラスをそのまま配置

  Http/
    Controllers/                … UseCase を呼び出すだけの薄い層にする
    Middleware/TimeGuardMiddleware.php … ReservationPolicy に判定を委譲するアダプタに縮小
```

## 主要ドメイン概念の整理

| 概念 | 現状の実装 | 提案後の位置づけ |
|---|---|---|
| 予約 | `Reservation` モデル（状態はカラムの文字列） | `Reservation` 集約ルート。状態遷移をメソッドで保護し、enum で状態管理 |
| 教室 | `Room` モデル＋2箇所のコントローラー | `Room` 集約。空き状況計算は `RoomSchedule` に一本化 |
| 教室の時限別状態 | `RoomStatus` テーブル（承認時のみ更新される投影データ） | `Reservation` と `RoomLesson` を真実源とした読み取り専用プロジェクションとして再定義 |
| 授業 | `RoomLesson` モデル（独立テーブル） | `Room` 集約の一部として `RoomSchedule` に統合 |
| 営業時間・時限ルール | `TimeGuardMiddleware` と `config/period.php` に分散 | `Period` 値オブジェクトと `ReservationPolicy` に統合 |
| 予約可能期間（2週間先まで） | コントローラーにハードコード | `ReservationPolicy` のルールとして集約 |
| 通知 | コントローラーから `Notification::create` を直接呼ぶ | ドメインイベントに対するリスナーの副作用として発生させる |

## 段階的移行ステップ

Big Bang 書き換えは避け、既存の `refactoring-fat-model.md` の Phase 1〜4（Fat Model 化・スコープ追加・グルーピング統一・Enum 導入）を前段として扱い、以降のステップで DDD レイヤーへ発展させます。

1. **ReservationStatus の enum 化**（リスク最小）
   既存 `refactoring-fat-model.md` の Phase 4 と同一。全コントローラー・モデルのマジックストリングを enum に置き換える。

2. **ReservationPolicy の抽出**
   `hasEndedPeriod()` / `isConsecutivePeriods()` / 2週間ルール / 1日1予約ルールをコントローラー外のドメインサービスへ移植する。`TimeGuardMiddleware` もこのサービスに判定を委譲し、営業時間ルールを単一ソース化する。

3. **RoomSchedule への統合**
   Admin / User 両 `RoomStatusController` に重複しているステータス計算ロジックを `RoomSchedule` サービスに一本化する。これにより整合性バグの温床だった重複実装を解消する。

4. **UseCase 層の導入**
   コントローラーを薄くし、`DB::transaction` による境界を UseCase 内に移動する。

5. **ドメインイベント化**
   承認・却下時の `Notification::create` と `Mail::send` をイベント発行＋リスナーに分離し、コントローラーからインフラ呼び出しを排除する。

6. **（任意）Repository パターンの導入**
   Eloquent をドメインから分離し、`RoomStatus` テーブルを「真実源」から「イベント経由で再構築されるプロジェクション」に位置づけ直すことで、キャンセル時の更新漏れバグを構造的に解消する。

## 既存バグ・設計課題（本リアーキテクチャで解消されるもの）

- ユーザー側 `cancel()` 実行時に `RoomStatus` が更新されず、承認済みとして残り続ける不整合
- 管理者の却下（`reject`）とユーザーの自己キャンセル（`cancel`）が同一ステータス `cancelled` に統合されており、履歴上区別できない
- `Admin/ReservationController::count()` のグルーピングロジックが `DashBoardController` と重複している（`refactoring-fat-model.md` 問題3でも指摘済み）

## 設計上の要検討事項（要合意）

- `reject` と `cancel` を別ステータスとして分離するか、現状のまま統合を維持するか
- `RoomStatus` テーブルをイベント経由の非同期プロジェクションまで発展させるか、今回は同期更新のまま整合性のみ修正するか
- Repository パターンまで導入するか、UseCase 層＋ Eloquent 直利用までに留めるか（プロジェクト規模に対して過剰設計になるリスクを踏まえて判断する）

## 参考

- [Fat Model / Skinny Controller リファクタリング方針](./refactoring-fat-model.md)
- [リファクタリング評価基準](./refactoring-checklist.md)
- [開発ルール](../development-rules.md)

---

## 改訂履歴

| 日付 | 変更内容 |
|---|---|
| 2026-10-09 | 初版作成（コードベース調査結果をもとに DDD リアーキテクチャ方針を策定） |
