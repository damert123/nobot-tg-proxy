<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramListen extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:listen';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Запуск обработчика сообщений из Telegram';

    protected $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;

    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Запуск обработчика сообщений Telegram...');
        $this->telegramService->listenForMessage();
    }
}
