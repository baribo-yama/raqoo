<div class="navigation">
    <ul>
        <li class="list active">
             <a href="{{ route('home') }}">
                <span class="icon"><ion-icon name="home-outline"></ion-icon></span>
                <span class="title">ホーム</span>
            </a>
        </li>
        <li class="list">
            <a href="{{ route('profile') }}">
                <span class="icon"><ion-icon name="person-circle-outline"></ion-icon></span>
                <span class="title">プロフィール</span>
            </a>
        </li>
        <li class="list">
            <a href="{{ route('settings') }}">
                <span class="icon"><ion-icon name="settings-outline"></ion-icon></span>
                <span class="title">設定</span>
            </a>
        </li>
        <li class="list">
            <a href="{{ route('notifications') }}">
                <span class="icon"><ion-icon name="radio-outline"></ion-icon></span>
                <span class="title">お知らせ</span>
            </a>
        </li>
        <li class="list">
            <a href="{{ route('terms') }}">
                <span class="icon"><ion-icon name="newspaper-outline"></ion-icon></span>
                <span class="title">利用規約</span>
            </a>
        </li>
        <li class="list">
            <a href="{{ route('logout') }}">
                <span class="icon"><ion-icon name="log-out-outline"></ion-icon></span>
                <span class="title">サインアウト</span>
            </a>
        </li>
    </ul>
</div>
