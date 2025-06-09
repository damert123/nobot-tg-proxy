<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class checkPeer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-peer';

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
        $data = unserialize(file_get_contents('storage/telegram_sessions/79172670513.madeline/safe.php'));

        dd(array_keys($data));
    }
}
