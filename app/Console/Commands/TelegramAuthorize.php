<?php

namespace App\Console\Commands;

use danog\MadelineProto\API;
use Illuminate\Console\Command;

class TelegramAuthorize extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:authorize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Авторизация Telegram-аккаунта через MadelineProto';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sessionFile = storage_path('telegram_sessions/user.madeline');

        try {
            $madelineProto = new API($sessionFile);

            // Запрос телефона
            $this->info('Введите номер телефона, привязанный к аккаунту (в формате +7...)');
            $phone = $this->ask('Номер телефона');
            if (empty($phone)) {
                throw new \InvalidArgumentException('Номер телефона не может быть пустым.');
            }

            // Отправка запроса на авторизацию
            $madelineProto->phoneLogin($phone);

            // Запрос кода
            $this->info('Введите код, который был отправлен в Telegram');
            $code = $this->ask('Код');
            if (empty($code)) {
                throw new \InvalidArgumentException('Код авторизации не может быть пустым.');
            }

            // Завершение авторизации
            $madelineProto->completePhoneLogin($code);

            $this->info('Авторизация успешно завершена.');

        } catch (\Exception $e) {
            $this->error('Ошибка авторизации: ' . $e->getMessage());
        }
    }
}
