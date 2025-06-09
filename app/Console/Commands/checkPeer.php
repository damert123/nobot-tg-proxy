<?php

namespace App\Console\Commands;

use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
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
        $sessionPath = storage_path('telegram_sessions/79172670513.madeline');

        $settings = (new Settings())->setAppInfo(
            (new Settings\AppInfo())
                ->setApiId(env('TELEGRAM_API_ID'))
                ->setApiHash(env('TELEGRAM_API_HASH'))
        );

        $Madeline = new API($sessionPath, $settings);

        $Madeline->start(); // подключаемся к сессии

        $dialogs = $Madeline->messages->getDialogs(['limit' => 100]);

        $peers = [];

        foreach ($dialogs['users'] ?? [] as $user) {
            $peers[] = [
                'id' => $user['id'],
                'username' => $user['username'] ?? null,
                'first_name' => $user['first_name'] ?? null,
                'access_hash' => $user['access_hash'] ?? null,
            ];
        }
        dd($peers);
    }
}
