<?php

namespace App\Console\Commands;

use App\Jobs\ProcessTelegramMessageJob;
use App\Modules\QueueMessagesPlanfix\ChatEntity;
use App\Modules\QueueMessagesPlanfix\MessageEntity;
use Illuminate\Console\Command;

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
        $chatId = ChatEntity::getOrderByChatId();

        if (!$chatId){
            $this->info('Нет чатов для обработки');
            return;
        }

        if (ChatEntity::hasInProgressMessages($chatId)){
            $this->info("Сообщения в чате {$chatId} уже обрабатываются");
        }

        $message = MessageEntity::findFirstPendingByChatId($chatId);

        if (!$message){
            $this->info("В чате {$chatId} нет сообщений со статусом pending.");
            return;
        }

        ProcessTelegramMessageJob::dispatch($message->getModel()->toArray());

        $this->info("Сообщение из чата {$chatId} отправлено в джобу");
    }
}
