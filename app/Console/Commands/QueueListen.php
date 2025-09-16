<?php

namespace App\Console\Commands;

use App\Jobs\ProcessTelegramMessageJob;
use App\Modules\QueueMessagesPlanfix\ChatEntity;
use App\Modules\QueueMessagesPlanfix\MessageEntity;
use App\Modules\TelegramAccount\TelegramAccountEntity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class QueueListen extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:queue-listen';

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
        try {
            $chats = ChatEntity::getAll();

            foreach ($chats as $chat){

                if($chat->hasInProgressMessages()){
                    continue;
                }

                $message = $chat->getFirstReadyRetryMessage();

                if (!$message && $chat->hasWaitingRetryMessages()){
                    continue;
                }

                if (!$message){
                    $message = $chat->getFirstMessageInPending();
                }

                if($message === null){
                    continue;
                }



                $message->setStatusInProgress();
                $account = TelegramAccountEntity::getTelegramAccount($message->getToken());
                Log::info('АККАУНТ ID ' . $account->getId());
                $delay = $this->calculateMessageDelay($message, $account);
                Log::info('Задержка ' . $account->getId());
                Log::info('Сообщение ' . $message->getMessage());


//                $message = $message->setCalculatedDelay($baseDelay);
                Log::info('ВЫЗЫВАЕМ ДЖОБУ');
                ProcessTelegramMessageJob::dispatch($message->getModel()->toArray(), $chat->getChatId(), $message)->delay(now()->addSeconds($delay));



            }

            return 0;
        }
        catch (\Throwable $e){
            Log::channel('planfix-messages')->error("Scheduled command failed: {$e->getMessage()}");
            return 1;
        }

    }

    private function calculateMessageDelay(MessageEntity $message, TelegramAccountEntity $account)
    {
//        $baseDelay = $message->typing_delay ?? 0;

        $prev = $message->findPreviousAccountMessageInOtherChat();

        if (!$prev){
            return 0;
        }

        return match ($account->getStatus()){
            TelegramAccountEntity::STATUS_ACTIVE => rand(1, 3),
            TelegramAccountEntity::STATUS_BROADCAST => rand(5, 15),
            TelegramAccountEntity::STATUS_THROTTLED => rand(20, 40),
            default => rand(2, 6)
        };

    }
}
