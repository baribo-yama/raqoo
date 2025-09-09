<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>教室管理 - ダッシュボード</title>
    <link rel="stylesheet" href="{{ asset('admin/kanrisya.css') }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>
    <div class="container">
        <!-- サイドバー -->
        <nav class="sidebar">
            <h2>教室管理</h2>
            <ul>
                <li id="dashboardBtn" class="menu-item active">ダッシュボード</li>
                <li id="noticeBtn" class="menu-item">お知らせ管理</li>
                <li id="historyBtn" class="menu-item">予約承認履歴</li>
            </ul>
        </nav>

        <!-- メイン画面 -->
        <main class="main">
            <!-- ダッシュボード -->
            <div id="dashboardScreen" class="screen active">
                <h1>raqoo管理画面</h1>

                <div class="content">

                    <!-- 日付選択 -->
                    <div>
                        <input type="date" id="datePicker">
                        <span id="selectedDate"></span>
                    </div>

                    <!-- 教室一覧 -->
                    <table>
                        <tr>
                            <th>教室名</th>
                            <th>収容人数</th>
                            @for ($i = 1; $i <= 6; $i++)
                                <th>{{ $i }}限</th>
                            @endfor
                        </tr>
                        @foreach ($rooms as $room)
                            <tr>
                                <td>{{ $room->name }}</td>
                                <td>{{ $room->capacity }}</td>
                                @for ($i = 1; $i <= 6; $i++)
                                    <td>
                                        <select class="status-select" data-room="{{ $room->id }}" data-period="{{ $i }}">
                                            <option value="available">利用可能</option>
                                            <option value="reserved">予約済み</option>
                                            <option value="in_use">利用中</option>
                                            <option value="unavailable">利用不可</option>
                                        </select>
                                    </td>
                                @endfor
                            </tr>
                        @endforeach
                    </table>

                    <!-- 予約一覧 -->
                    <div class="reservation-panel">
                        <section class="request-list">
                            <h3>予約申請一覧</h3>
                            <table>
                                <section class="request-list">

                                    <table>
                                        <thead>
                                            <tr>
                                                <th>申請時間</th>
                                                <th>教室</th>
                                                <th>申請者名</th>
                                                <th>詳細</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- 1件目 -->
                                            <tr class="request-row" data-datetime="2025-08-07 07:35" data-name="田中太郎"
                                                data-purpose="録音のための利用">
                                                <td>07:35</td>
                                                <td>Dance Studio A</td>
                                                <td>田中太郎</td>
                                                <td><button class="detail-toggle">:</button></td>
                                            </tr>
                                            <tr class="detail-row" style="display: none;">
                                                <td colspan="4" class="detail-content"></td>
                                            </tr>

                                            <!-- 2件目 -->
                                            <tr class="request-row" data-datetime="2025-08-07 10:15" data-name="佐藤花子"
                                                data-purpose="ステージパフォーマンス準備">
                                                <td>10:15</td>
                                                <td>Lesson Room 1</td>
                                                <td>佐藤花子</td>
                                                <td><button class="detail-toggle">:</button></td>
                                            </tr>
                                            <tr class="detail-row" style="display: none;">
                                                <td colspan="4" class="detail-content"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </section>


                            </table>
                        </section>

                        <section class="current-use">
                            <h3>現在使用されている教室</h3>
                            <table>
                                <tr>
                                    <th>使用中の教室</th>
                                    <th>教室使用者名</th>
                                </tr>
                                <tr>
                                    <td>Ensemble Room</td>
                                    <td>田中太郎</td>
                                </tr>
                                <tr>
                                    <td>Pre-Production Room</td>
                                    <td>田中太郎</td>
                                </tr>
                                <tr>
                                    <td>Dance Studio A</td>
                                    <td>田中太郎</td>
                                </tr>
                                <tr>
                                    <td>501</td>
                                    <td>田中太郎</td>
                                </tr>
                                <tr>
                                    <td>503</td>
                                    <td>田中太郎</td>
                                </tr>
                                <tr>
                                    <td>Pc Room 2</td>
                                    <td>田中太郎</td>
                                </tr>
                                <tr>
                                    <td>602 B</td>
                                    <td>田中太郎</td>
                                </tr>
                                <tr>
                                    <td>Ls-1</td>
                                    <td>田中太郎</td>
                                </tr>
                                <tr>
                                    <td>Dressing Room</td>
                                    <td>田中太郎</td>
                                </tr>
                            </table>
                        </section>
                    </div>
                </div>
            </div>

            <!-- お知らせ管理 -->
            <div id="noticeScreen" class="screen">
                <h1>お知らせ管理</h1>
                <p>ここにお知らせ管理画面の内容が入ります。</p>
            </div>

            <!-- 予約承認履歴 -->
            <div id="historyScreen" class="screen">
                <h1>予約承認履歴</h1>
                <p>ここに予約承認履歴の内容が入ります。</p>
            </div>
        </main>
    </div>

    <!-- JavaScriptで画面切り替え -->
    <script src="{{ asset('admin/kanrisya.js') }}"></script>
</body>

</html>
