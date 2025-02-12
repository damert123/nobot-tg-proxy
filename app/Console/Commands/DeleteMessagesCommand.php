<?php

namespace App\Console\Commands;

use App\Modules\QueueMessagesPlanfix\MessageEntity;
use Illuminate\Console\Command;

class DeleteMessagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-messages-command';

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
        $completeMessages = MessageEntity::getCompleteMessagesAll();

        foreach ($completeMessages as $message) {
            $message->delete();
        }
        $this->info('All completed messages have been deleted.');
    }
}
