@extends('layouts.app')
@include('inc.header')

@section('content')

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Панель управления</h1>

        <div class="bg-white shadow rounded-lg p-6">
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
    </div>



@endsection
