@extends('layouts.app')
@include('inc.header')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Добавить интеграцию Planfix</h1>

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

        <form action="{{ route('planfix.store') }}" method="POST" class="bg-white shadow rounded-lg p-6">
            @csrf

            <!-- Название интеграции -->
            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700">Название интеграции</label>
                <input type="text" name="name" id="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Введите название интеграции" required>
                @error('title')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Provider ID -->
            <div class="mb-4">
                <label for="provider_id" class="block text-sm font-medium text-gray-700">Provider ID</label>
                <input type="text" name="provider_id" id="provider_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Введите Provider ID" required>
                @error('provider_id')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Token -->
            <div class="mb-4">
                <label for="token" class="block text-sm font-medium text-gray-700">Ключ авторизации</label>
                <input type="text" name="token" id="token" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Введите токен" required>
                @error('token')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Выбор Telegram-аккаунта -->
            <div class="mb-4">
                <label for="telegram_account_id" class="block text-sm font-medium text-gray-700">Telegram-аккаунт</label>
                <select name="telegram_account_id" id="telegram_account_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    <option value="" disabled selected>Выберите Telegram-аккаунт</option>
                    @foreach ($telegramAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->title }} ({{ $account->phone }})</option>
                    @endforeach
                </select>
                @error('telegram_account_id')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Кнопка отправки -->
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Сохранить интеграцию
            </button>
        </form>
    </div>
@endsection
