
@section('header')
<header class="bg-gray-800 text-white">
    <div class="container mx-auto px-4 py-4 flex justify-between items-center">
        <!-- Логотип -->
        <div class="text-lg font-bold">
            <a href="{{ url('/') }}">{{ config('app.name', 'Laravel') }}</a>
        </div>

        <!-- Навигация -->
        <nav class="space-x-4">
            <a href="/" class="hover:underline">Главная</a>
            @auth
                <a href="{{ route('dashboard') }}" class="hover:underline">Панель управления</a>
            @endauth
        </nav>

        <!-- Управление авторизацией -->
        <div>
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
                        Выйти
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                    Войти
                </a>
                <a href="{{ route('register') }}" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded ml-2">
                    Регистрация
                </a>
            @endauth
        </div>
    </div>
</header>

@endsection
