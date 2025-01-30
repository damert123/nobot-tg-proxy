<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RestartSupervisor extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:restart-supervisor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Перезапуск Supervisor для обновления активных сессий';

    /**
     * Execute the console command.
     */


    public function handle()
    {
        $this->info('Перезапуск Supervisor...');

        // Выполняем команду рестарта Supervisor
        $output = shell_exec('supervisorctl restart telegram_listener 2>&1');

        // Логируем результат
        Log::info('Результат рестарта Supervisor:', ['output' => $output]);

        $this->info('Supervisor перезапущен.');
    }
}
