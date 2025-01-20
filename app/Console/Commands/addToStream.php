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
        $message = [
            'name' => 'Test message',
            'time' => now(),
        ];

        $this->info("Adding message to stream...");

        Redis::command('XADD', [
            $streamName,
            '*', // Идентификатор автоматически создается
            ...array_map(fn($key, $value) => [$key, $value], array_keys($message), $message)
        ]);

        $this->info("Reading messages from stream...");
        $response = Redis::command('XREAD', [
            'BLOCK', 0, // Без блокировки
            'COUNT', 5, // Чтение до 5 записей
            'STREAMS', $streamName, '0'
        ]);

        $this->info("Stream content:");
        dd($response); // Выведет содержимое потока
    }
}
