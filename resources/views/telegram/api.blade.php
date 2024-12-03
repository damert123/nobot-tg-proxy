@extends('layouts.app')
@include('inc.header')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Настройка Telegram API</h1>

        <!-- Ошибки валидации -->
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded relative mt-4">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <!-- Успешное сохранение -->
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded relative mt-4">
                <p>{{ session('success') }}</p>
            </div>
        @endif

        <!-- Форма -->
        <form action="{{ route('telegram.api.store') }}" method="POST" class="bg-white shadow rounded-lg p-6">
            @csrf

            <!-- API ID -->
            <div class="mb-4">
                <label for="api_id" class="block text-sm font-medium text-gray-700">Telegram API ID</label>
                <input type="text" name="api_id" id="api_id"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                       placeholder="Введите API ID" value="{{ old('api_id') }}" required>
                @error('api_id')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- API HASH -->
            <div class="mb-4">
                <label for="api_hash" class="block text-sm font-medium text-gray-700">Telegram API HASH</label>
                <input type="text" name="api_hash" id="api_hash"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                       placeholder="Введите API HASH" value="{{ old('api_hash') }}" required>
                @error('api_hash')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Кнопка отправки -->
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Сохранить
            </button>
        </form>
    </div>
@endsection
