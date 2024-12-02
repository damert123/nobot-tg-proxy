@extends('layouts.app')
@include('inc.header')

@section('content')

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Все Интеграции Planfix</h1>

        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Список всех подключенных интеграций Planfix</h2>

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

            @if($integrations->isEmpty())
                <p>Нет подключенных интеграций.</p>
            @else
                <!-- Таблица интеграций -->
                <table class="min-w-full table-auto border-collapse">
                    <thead>
                    <tr class="bg-gray-100">
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">Название</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">Provider ID</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">Token (Вводить в PLANFIX)</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">Telegram</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">Статус</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($integrations as $integration)
                        <tr class="border-b">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $integration->id }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $integration->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $integration->provider_id }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $integration->token }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $integration->telegram_account_info  ?? 'Не привязан' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $integration->status ?? 'Не активен' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900 flex items-center space-x-4">
                                <form action="{{ route('planfix.destroy', $integration->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-500 text-white p-2 rounded hover:bg-red-600">
                                        Удалить
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

@endsection
