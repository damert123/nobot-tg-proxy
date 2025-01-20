<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class addToStream extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis:test-stream';

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
        $streamName = 'mystream';

        // XADD - добавление записи
        $this->info("Adding message to stream...");

        $response = Redis::command('XADD', [
            $streamName,
            '*',                // Генерация уникального ID
            'name', 'Test message', // Поля и их значения
            'time', now()->toDateTimeString(),
        ]);

        $this->info("Message added with ID: $response");

        // XREAD - чтение записей
        $this->info("Reading messages from stream...");

        $response = Redis::command('XREAD', [
            'COUNT', 5,         // Чтение до 5 записей
            'STREAMS',          // Потоки
            $streamName, '0',   // Название потока и стартовый ID
        ]);

        $this->info("Stream content:");
        dd($response); // Вывод содержимого потока
    }
}
