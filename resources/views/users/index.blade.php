@extends('layouts.app')
@include('inc.header')

@section('content')

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Пользователи</h1>

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

        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">Список пользователей</h2>

            <table class="min-w-full table-auto border-collapse">
                <thead>
                <tr class="bg-gray-100">
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">ID</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">Имя</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">Роль</th>
                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase">Действия</th>
                </tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    <tr class="border-b">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $user->id }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $user->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $user->email }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <form action="{{ route('users.updateRole', $user->id) }}" method="POST" class="flex items-center">
                                @csrf
                                @method('PUT')
                                <select name="role" class="border rounded px-4 py-2 w-3/4">
                                    <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>user</option>
                                    <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>admin</option>
                                </select>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                Сохранить
                            </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

@endsection
