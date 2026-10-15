<x-mail::message>
# 予約申請が却下されました

予約申請内容
- 教室: {{ $reservation->room->name }}
- 日付: {{ $reservation->date->format('Y-m-d') }}
- 時限: {{ $reservation->period }}限

<x-mail::button :url="url('/reservations')">
予約一覧を確認する
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
