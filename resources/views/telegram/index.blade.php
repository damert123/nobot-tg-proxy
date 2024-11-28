@extends('layouts.app')
@include('inc.header')

@section('content')

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Все Telegram-аккаунты</h1>

        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Список всех подключенных Telegram-аккаунтов</h2>

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
                                <!-- Заглушки для кнопок, добавьте свои иконки или изображения -->
                                <button class="bg-red-500 text-white p-2 rounded hover:bg-red-600" onclick="window.location.href='#'">
                                    Удалить
                                </button>
                                <button class="bg-yellow-500 text-white p-2 rounded hover:bg-yellow-600" onclick="window.location.href='#'">
                                    Пауза
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>


@endsection
