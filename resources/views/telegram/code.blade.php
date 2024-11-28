@extends('layouts.app')
@include('inc.header')
@section('content')
    <div class="container mx-auto p-4">
        <div class="max-w-md mx-auto bg-white shadow-lg rounded-lg overflow-hidden">
            <div class="px-6 py-4">
                <h2 class="text-2xl font-bold text-gray-700">Введите код подтверждения</h2>
                <p class="text-gray-600 mt-2">Код отправлен на номер: <strong>{{ $phone }}</strong></p>
                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded relative mt-4">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif
                <form action="{{ route('telegram.verify') }}" method="POST" class="mt-4">
                    @csrf
                    <input type="hidden" name="phone" value="{{ $phone }}">

                    <label for="code" class="block text-gray-700">Код подтверждения:</label>
                    <input type="text" id="code" name="code" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring focus:ring-blue-100 mt-2" required>

                    <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring focus:ring-blue-200 mt-4">
                        Подтвердить
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
