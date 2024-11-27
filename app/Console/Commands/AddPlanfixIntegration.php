<?php

namespace App\Console\Commands;

use App\Models\PlanfixIntegration;
use App\Models\TelegramAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AddPlanfixIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'planfix:add';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Добавить интеграцию Planfix в базу данных';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $telegramAccounts = TelegramAccount::all();
        if ($telegramAccounts->isEmpty()){
            $this->error('Нет доступных Telegram аккаунтов');
            return Command::FAILURE;
        }

        $this->info('Доступные Telegram-аккаунты');
        foreach ($telegramAccounts as $account){
            $this->line("ID: {$account->id}, Session: {$account->session_path}");
        }

        $telegramAccountId = $this->ask('Введите ID Telegram-аккаунта для интеграции');
        $telegramAccount = TelegramAccount::find($telegramAccountId);

        if (!$telegramAccount){
            $this->error('Телеграм аккаунт не найден.');
            return Command::FAILURE;
        }

        $providerId = $this->ask('Введите provider_id для интеграции');
        $token = Str::random(32);

        $planfixToken = $this->ask('Введите ключ авторизации чата');
        $planfixName = $this->ask('Имя интеграции (любое или какое указано у вас в Planfix)');


        $integration = PlanfixIntegration::create([
            'provider_id' => $providerId,
            'planfix_token' => $planfixToken,
            'name' => $planfixName,
            'token' => $token,
            'telegram_account_id' => $telegramAccount->id,
        ]);

        $this->info('Интеграция успешно создана.');
        $this->info("Provider ID: {$integration->provider_id}");
        $this->info("Token: {$integration->token}");

        return Command::SUCCESS;




    }
}
