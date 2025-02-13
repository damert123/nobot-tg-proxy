<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Modules\ApiNoBot\ApiNobotService;
use App\Modules\TelegramMessagesToPlanfix\TgMessagesEntity;
use Illuminate\Console\Command;

class TestJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-json';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {


        $data = [
            'provider_id' => '123456',
            'chat_id' => 987654321,
            'planfix_token' => 'test_token_123',
            'message' => 'Тестовое сообщение',
            'title' => 'Тестовый заголовок',
            'contact_id' => 111222333,
            'contact_name' => 'Иван',
            'contact_last_name' => 'Иванов',
            'telegram_username' => 'ivan_test',
            'contact_data' => 'Телефон: +79991234567',
            'attachments_name' => 'test_file.jpg',
            'attachments_url' => 'https://example.com/test_file.jpg',
            'status' => 'pending',
        ];

        $resp = TgMessagesEntity::create($data);

        dd($resp);

    }
}
