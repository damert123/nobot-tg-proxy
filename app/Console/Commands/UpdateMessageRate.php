<?php

namespace App\Console\Commands;

use App\Modules\QueueMessagesPlanfix\MessageEntity;
use App\Modules\TelegramAccount\TelegramAccountEntity;
use Illuminate\Console\Command;

class UpdateMessageRate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounts:update-message-rate';

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
        $accounts = TelegramAccountEntity::getAllActiveAccountTgToCrm();

        /** @var \Illuminate\Support\Collection<int, TelegramAccountEntity> $accounts */

        foreach ($accounts as $account){
            $count = MessageEntity::countSentMessagesForAccount($account);

            $account->updateMessageRate($count);
        }

        $this->info("Message rate обновлен для {$accounts->count()} аккаунтов");

    }
}
