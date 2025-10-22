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
    protected $signature = 'telegram:listen {--session=}';


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

        $sessionPath = $this->option('session');

        if (!$sessionPath) {
            $this->error('Не указан путь к сессии (--session=)');
            return 1;
        }
        $this->info('Запуск обработчика сообщений Telegram...');
        $this->telegramService->listenForMessage($sessionPath);

        return 0;
    }
}
