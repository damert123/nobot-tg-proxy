<?php

namespace App\Console\Commands;

use App\Jobs\ProcessTelegramMessageJob;
use App\Modules\QueueMessagesPlanfix\ChatEntity;
use App\Modules\QueueMessagesPlanfix\MessageEntity;
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

                $message = $chat->getFirstMessageInPending();

                if($message === null){
                    continue;
                }

                $message->setStatusInProgress();

                ProcessTelegramMessageJob::dispatch($message->getModel()->toArray(), $chat->getChatId(), $message);

            }

            return 0;
        }
        catch (\Throwable $e){
            Log::channel('planfix-messages')->error("Scheduled command failed: {$e->getMessage()}");
            return 1;
        }

    }
}
