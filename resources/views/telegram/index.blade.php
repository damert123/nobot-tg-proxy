@extends('layouts.app')
@include('inc.header')

@section('content')

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Все Telegram-аккаунты</h1>

        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Список всех подключенных Telegram-аккаунтов</h2>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded relative mt-4">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded relative mt-4">
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            @if($accounts->isEmpty())
                <p>Нет подключенных аккаунтов.</p>
            @else
                <!-- Таблица аккаунтов -->
                <table class="min-w-full table-auto border-collapse">
                    <thead>
                    <tr class="bg-gray-100">
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">Название</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">Телефон</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">Telegram ID</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">Статус</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($accounts as $account)
                        <tr class="border-b">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $account->id }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $account->title }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $account->phone }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $account->telegram_id }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $account->status }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 flex items-center space-x-4">
                                @if ($account->status === 'Ожидает код')
                                    <a href="{{ route('telegram.code', ['phone' => $account->phone]) }}"
                                       class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                        Ввести код
                                    </a>
                                @endif

                                <form action="{{ route('telegram.destroy', $account->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-500 text-white p-2 rounded hover:bg-red-600">
                                        Удалить
                                    </button>
                                </form>

                               @if ($account->status === 'Разлогинен')
                                        <a href="{{ route('telegram.code', ['phone' => $account->phone]) }}"
                                           class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                            Войти
                                        </a>
                                @endif
                                @if ($account->status === 'Пауза')
                                        <form action="{{route('telegram.start', $account->id)}}" method="POST">
                                            @csrf
                                    <button type="submit" class="bg-green-500 text-white p-2 rounded hover:bg-green-600">
                                        Старт
                                    </button>
                                        </form>
                                @else
                                        <form action="{{route('telegram.stop', $account->id)}}" method="POST">
                                            @csrf
                                        <button type="submit" class="bg-yellow-500 text-white p-2 rounded hover:bg-yellow-600">
                                        Пауза
                                         </button>
                                        </form>
                                @endif



                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>


@endsection
