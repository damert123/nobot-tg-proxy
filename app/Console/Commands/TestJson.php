<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Modules\ApiNoBot\ApiNobotService;
use Illuminate\Console\Command;

class TestJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-json';

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

        $crmService = new ApiNobotService();

        $taskId = 208991;

        $task = $crmService->getTask($taskId);



        $contactId = $task['task']['assigner']['id'];
        $contact = $crmService->getContact($contactId);
        $telegramLink = $contact['contact']['telegram'] ?? null;


        $parsedUsername = $crmService->extractUsernameFromLink($telegramLink);

        dd($parsedUsername);


    }
}
