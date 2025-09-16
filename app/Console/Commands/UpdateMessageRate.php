<?php

namespace App\Console\Commands;

use App\Modules\PlanfixIntegration\PlanfixIntegrationEntity;
use App\Modules\QueueMessagesPlanfix\MessageEntity;
use App\Modules\TelegramAccount\TelegramAccountEntity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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

    const THROTTLE_ENTER_RATE = 5;
    const THROTTLE_EXIT_RATE =  3;
    const THROTTLE_MIN_DURATION = 300; //5 мин

    const BROADCAST_DUPLICATES = 3;
    const BROADCAST_MIN_DURATION = 180;


    public function handle()
    {
        $accounts = TelegramAccountEntity::getAllActiveAccountTgToCrm();

        /** @var \Illuminate\Support\Collection<int, TelegramAccountEntity> $accounts */

        foreach ($accounts as $account){


            $count = MessageEntity::countSentMessagesForAccount($account);

            $duplicatesCount = MessageEntity::countDuplicateMessagesLastMinute($account);

            $now = now();
            $currentStatus = $account->getStatus();

            $sinceChange = $account->getStatusChangeAt() ? $now->diffInSeconds($account->getStatusChangeAt()) : 0;

            Log::info('Статус длиться уже ' . $sinceChange);

            if ($currentStatus === TelegramAccountEntity::STATUS_THROTTLED){
                if ($count <= self::THROTTLE_EXIT_RATE && $sinceChange >= self::THROTTLE_MIN_DURATION){
                    $newStatus = TelegramAccountEntity::STATUS_ACTIVE;
                }
                else{
                    $newStatus = TelegramAccountEntity::STATUS_THROTTLED;
                }
            }
            elseif($currentStatus === TelegramAccountEntity::STATUS_BROADCAST){
                if ($duplicatesCount <= self::BROADCAST_DUPLICATES && $sinceChange >= self::BROADCAST_MIN_DURATION){
                    if ($count >= self::THROTTLE_ENTER_RATE){
                        $newStatus = TelegramAccountEntity::STATUS_THROTTLED;
                    }else{
                        $newStatus = TelegramAccountEntity::STATUS_ACTIVE;
                    }
                }
                else{
                    if ($count >= self::THROTTLE_ENTER_RATE){
                        $newStatus = TelegramAccountEntity::STATUS_THROTTLED;
                    }else{
                        $newStatus = TelegramAccountEntity::STATUS_ACTIVE;
                    }
                }
            }

            else {
                if ($count >= self::THROTTLE_ENTER_RATE){
                    $newStatus = TelegramAccountEntity::STATUS_THROTTLED;

                }elseif ($duplicatesCount >= self::BROADCAST_DUPLICATES){
                    $newStatus = TelegramAccountEntity::STATUS_BROADCAST;

                }else{
                    $newStatus = TelegramAccountEntity::STATUS_ACTIVE;
                }

            }

            if ($newStatus !== $currentStatus){
                $account->updateStatus($newStatus, $now);

//                Log::channel('status-account')->info("Account status changed", [
//                    'token' => PlanfixIntegrationEntity::findByTelegramAccountId($account->getId())->getToken(),
//                    'from' => $currentStatus,
//                    'to' => $newStatus,
//                    'message_rate' => $count,
//                    'duplicates' => $duplicatesCount,
//                ]);
            }else{
                $account->updateMessageRate($count);
            }
        }


        $this->info("Message rate + status обновлены для {$accounts->count()} аккаунтов");

        return self::SUCCESS;

    }
}
