<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 whitespace-pre-line">
        {!! nl2br(e("設定したメールアドレス宛に確認用のメールアドレスをお送りしました。\nご確認お願いします。\n来ていない場合は下の「再送する」ボタンからメールを再送してください。"))  !!}
    <!-- 　　↑/nを<br>にするやつ　改行　 -->
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ __('登録時のメールアドレス宛に新しい認証リンクをお送りしました。') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('再送する') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{ __('ログアウト') }}
            </button>
        </form>
    </div>
</x-guest-layout>
