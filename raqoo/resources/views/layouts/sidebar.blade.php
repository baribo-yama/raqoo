<div class="navigation">
    <ul>
        <li class="list active">
            <a href="{{ route('home') }}" aria-current="page">
                <span class="icon"><ion-icon name="home-outline" aria-hidden="true"></ion-icon></span>
                <span class="title">ホーム</span>
            </a>
        </li>
        <li class="list">
            <a href="{{ route('profile') }}">
                <span class="icon"><ion-icon name="person-circle-outline" aria-hidden="true"></ion-icon></span>
                <span class="title">プロフィール</span>
            </a>
        </li>
        <li class="list">
            <a href="{{ route('settings') }}">
                <span class="icon"><ion-icon name="settings-outline" aria-hidden="true"></ion-icon></span>
                <span class="title">設定</span>
            </a>
        </li>
        <li class="list">
            <a href="{{ route('notifications') }}">
                <span class="icon"><ion-icon name="radio-outline" aria-hidden="true"></ion-icon></span>
                <span class="title">お知らせ</span>
            </a>
        </li>
        <li class="list">
            <a href="{{ route('terms') }}">
                <span class="icon"><ion-icon name="newspaper-outline" aria-hidden="true"></ion-icon></span>
                <span class="title">利用規約</span>
            </a>
        </li>
        <li class="list">
            <a href="{{ route('logout') }}">
                <span class="icon"><ion-icon name="log-out-outline" aria-hidden="true"></ion-icon></span>
                <button type="submit" style="background: none; border: none; padding: 0; margin: 0; color: inherit; font: inherit; cursor: pointer; display: flex; align-items: center;">
                    <span class="icon"><ion-icon name="log-out-outline"></ion-icon></span>
                    <span class="title">サインアウト</span>
                </button>
            </form>
        </li>
    </ul>
</div>
