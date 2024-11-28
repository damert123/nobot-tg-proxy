@extends('layouts.app')
@include('inc.header')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Добавить Telegram-аккаунт</h1>

        <form action="{{ route('telegram.store') }}" method="POST" class="bg-white shadow rounded-lg p-6">
            @csrf
            <div class="mb-4">
                <label for="phone" class="block text-sm font-medium text-gray-700">Номер телефона</label>
                <input type="text" name="phone" id="phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="+7XXXXXXXXXX" required>
            </div>
            <div class="mb-4">
                <label for="title" class="block text-sm font-medium text-gray-700">Заголовок аккаунта</label>
                <input type="text" name="title" id="title" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Название аккаунта" required>
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Продолжить
            </button>
        </form>
    </div>
@endsection
