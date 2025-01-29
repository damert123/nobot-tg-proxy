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
        $link = 'decolame888';

        $res = preg_replace('/^(https?:\/\/)?(t\.me\/|@)/', '', $link);

        dd($res ? '@' . $res : null);

    }
}
