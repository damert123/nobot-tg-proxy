<?php

namespace App\Console\Commands;

use App\Models\TelegramAccount;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings\AppInfo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     *
     */

    protected $settings;
    public function handle()
    {


        try {

            $settings = $this->settings = (new AppInfo)
                ->setApiId(env('TELEGRAM_API_ID'))
                ->setApiHash(env('TELEGRAM_API_HASH'));


            // Запрос телефона
            $this->info('Введите номер телефона, привязанный к аккаунту (в формате +7...)');
            $phone = $this->ask('Номер телефона');
            if (empty($phone)) {
                throw new \InvalidArgumentException('Номер телефона не может быть пустым.');
            }

            $sessionFile = storage_path("telegram_sessions/{$phone}.madeline");

            $madelineProto = new API($sessionFile, $settings);


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

            $madelineProto->start(); //запускаем сессию

            $self = $madelineProto->getSelf(); //получаем данные сессии

            if (!isset($self['id'])) {
                throw new \RuntimeException('Не удалось получить ID Telegram-аккаунта.');
            }

            // Сохранение данных в базу

            try {
                TelegramAccount::create([
                    'telegram_id' => $self['id'],
                    'session_path' => $sessionFile,
                ]);
                $this->info('Авторизация успешно завершена.');
            } catch (\Exception $e) {
                $this->error('Не удалось сохранить сессию: ' . $e->getMessage());
            }




//            $self = $madelineProto->getSelf();
//            Log::channel('tg-messages')->info('ID себя же ' . $self);


        } catch (\Exception $e) {
            $this->error('Ошибка авторизации: ' . $e->getMessage());
        }

    }
}
