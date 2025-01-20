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
        // Настройки
        $streamName = 'mystream';

        // XADD - добавление записи
        $message = [
            'name' => 'Test message',
            'time' => now(),
        ];

        // Правильное добавление параметров в команду XADD
        $args = [$streamName, '*']; // имя потока и символ * для авто-генерации id

        foreach ($message as $key => $value) {
            // Добавляем пару ключ-значение
            $args[] = $key;
            $args[] = $value;
        }

        $this->info("Adding message to stream...");
        $response = Redis::command('XADD', $args);

        // Проверим результат добавления
        $this->info("Message added with ID: " . $response);

        // XREAD - чтение записей
        $this->info("Reading messages from stream...");
        $response = Redis::command('XREAD', [
            'BLOCK', 0, // Без блокировки
            'COUNT', 5, // Чтение до 5 записей
            'STREAMS', $streamName, '0'
        ]);

        // Выводим результат
        $this->info("Stream content:");
        dd($response); // Выведет содержимое потока
    }
}
