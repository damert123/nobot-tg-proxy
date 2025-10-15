<?php

namespace App\Modules\QueueServiceMessagesToTelegram;

use App\Jobs\SendTelegramMessageJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessMessageQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messages-service:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending service messages from queue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting message queue processing...');
        Log::channel('top-up-messages')->info('Starting message queue processing...');

        $message = QueueServiceMessagesEntity::getPendingMessageScheduled();

        if (!$message) {
            $this->info('No messages to process at this time');
            Log::channel('top-up-messages')->debug('No messages to process at this time');
            return;
        }

        try {
            $this->info("Processing message ID: {$message->getId()}");
            Log::channel('top-up-messages')->info("Processing message ID: {$message->getId()}");

            $message->updateStatus(QueueServiceMessagesEntity::STATUS_PROCESSING);

            SendTelegramMessageJob::dispatch(
                $message->getTelegramId(),
                $message->getMessage(),
                $message->getTelegramLink()
            )->onQueue('tg-service-messages-top-up');

            $message->updateStatus(QueueServiceMessagesEntity::STATUS_SENT);

            $this->info("Message {$message->getId()} sent successfully");
            Log::channel('top-up-messages')->info("Message {$message->getId()} sent successfully");


        } catch (\Exception $e) {
            $message->updateStatus(QueueServiceMessagesEntity::STATUS_FAILED);

            $this->error("Failed to send message {$message->getId()}: {$e->getMessage()}");
            Log::channel('top-up-messages')->error("Failed to process message {$message->getId()}: {$e->getMessage()}");
        }

    }
}
