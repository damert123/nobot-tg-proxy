<?php

namespace App\Http\Requests\Planfix;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:planfix_integrations,name',
            'provider_id' => 'required|string|max:255|unique:planfix_integrations,provider_id',
            'token' => 'required|string|max:255|unique:planfix_integrations,token',
            'telegram_account_id' => 'required|integer|exists:telegram_accounts,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Название интеграции обязательно для заполнения.',
            'name.unique' => 'Интеграция с таким названием уже существует.',
            'provider_id.required' => 'Provider ID обязателен для заполнения.',
            'provider_id.unique' => 'Этот Provider ID уже используется.',
            'token.required' => 'Токен обязателен для заполнения.',
            'token.unique' => 'Этот токен уже используется.',
            'telegram_account_id.required' => 'Выберите Telegram-аккаунт.',
            'telegram_account_id.exists' => 'Такого аккаунта telegram нет',
        ];
    }
}
