<?php

namespace App\Console\Commands;

use App\Modules\QueueMessagesPlanfix\ChatEntity;
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
        ChatEntity::hasInProgressMessages();
    }
}
