@extends('layouts.app')

@section('content')
    <form method="POST" action="{{ route('telegram.verifyTwoFactorCode') }}">
        @csrf
        <input type="hidden" name="phone" value="{{ $phone }}">
        <div>
            <label for="twofactor_code">Введите пароль двухфакторной аутентификации</label>
            <input type="password" id="twofactor_code" name="twofactor_code" required>
        </div>
        <button type="submit">Подтвердить</button>
    </form>
@endsection
