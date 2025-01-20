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
     * @throws \RedisException
     */
    public function handle()
    {
        $streamName = 'mystream';

        // XADD - добавление записи
        $this->info("Adding message to stream...");

//        $response = Redis::connection()->client()->rawCommand(
//            'XADD',
//            $streamName,
//            '*',
//            'name', 'Test message',
//            'time', now()->toDateTimeString()
//        );

        $response = Redis::connection()->client()->xAdd(
            $streamName,
            '*', // Автогенерация ID
            [
                'name' => 'Test message',
                'time' => now()->toDateTimeString(),
            ]
        );

        $this->info("Message added with ID: $response");

        // XREAD - чтение записей
        $this->info("Reading messages from stream...");
        $messages = Redis::connection()->client()->rawCommand(
            'XREAD',
            'COUNT', 5,
            'STREAMS',
            $streamName,
            '0'
        );

        $this->info("Stream content:");
        dd($messages);
    }
}
