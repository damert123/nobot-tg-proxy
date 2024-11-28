<?php

namespace App\Http\Requests\Telegram;

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
            'phone' => 'required|string|unique:telegram_accounts,phone|min:11|max:11',
            'title' => 'required|string|max:255|unique:telegram_accounts,title',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Укажите номер телефона.',
            'phone.unique' => 'Этот номер телефона уже зарегистрирован.',
            'title.required' => 'Укажите заголовок аккаунта.',
            'title.unique' => 'Такое имя телеграма уже есть.',
        ];
    }
}
