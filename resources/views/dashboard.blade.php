@extends('layouts.app')
@include('inc.header')
@section('content')

    <div class="container mx-auto px-4 py-8 ">
        <h1 class="text-2xl font-bold mb-6">Панель управления</h1>

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

        <!-- Управление Telegram -->
        <div class="bg-white shadow rounded-lg p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Управление Telegram-аккаунтами</h2>
            <p class="mb-4">Добавьте новый Telegram-аккаунт для интеграции.</p>

            <a href="{{ route('telegram.add') }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Добавить Telegram-аккаунт
            </a>

            <div class="mt-6">
                <a href="{{ route('telegram.index') }}" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    Посмотреть аккаунты
                </a>
            </div>
        </div>

        <!-- Интеграция с Planfix -->
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Интеграция с Planfix</h2>
            <p class="mb-4">Настройте интеграцию с Planfix для управления задачами.</p>

            <a href="{{ route('planfix.add') }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Добавить интеграцию с Planfix
            </a>

            <div class="mt-6">
                <a href="{{ route('planfix.index') }}" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    Посмотреть интеграции
                </a>
            </div>
        </div>
    </div>
@endsection


