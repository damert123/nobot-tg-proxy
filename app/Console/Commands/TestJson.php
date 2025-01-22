<?php

namespace App\Console\Commands;

use App\Models\Message;
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
        $message = Message::first()->toArray();
        $res = is_array($message['attachments']);
        dd($res);
                                        // Замените 1 на ID нужного сообщения
        if ($message) {
            $attachments = json_decode($message->attachments, true);
            $this->info('Decoded JSON:');
            print_r($attachments);
        } else {
            $this->error('Message not found.');
        }
    }
}
