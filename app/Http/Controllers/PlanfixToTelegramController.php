<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTelegramMessageJob;
use App\Services\PlanfixService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class PlanfixToTelegramController extends Controller
{
    protected PlanfixService $planfixService;

    public function __construct(PlanfixService $planfixService)
    {
        $this->planfixService = $planfixService;
    }

    public function handle(Request $request)
    {
        try {
            Log::channel('planfix-messages')->info('Planfix webhook received:', $request->all());

            $data = $request->all();
            $this->planfixService->validateWebhookData($data);

            $chatId = $data['chatId'] ?? null;

            if (!$chatId) {
                throw new \Exception('chatId is required');
            }

            $streamKey = "stream:chat:$chatId";


            Log::info('Adding to stream:', [
                'streamKey' => $streamKey,
                'chatId' => $chatId,
                'message' => json_encode($data),
            ]);

            Redis::command('XADD', [
                $streamKey,
                '*',
                'cmd', $data['cmd'],
                'providerId', $data['providerId'],
                'token', $data['token'],
                'message', $data['message'],
                'messageId', $data['messageId'],
                'userName', $data['userName'],
                'userLastName', $data['userLastName'],
                'userIco', $data['userIco'],
                'taskEmail', $data['taskEmail'],
                'chatId', $data['chatId'],
                'integration', $data['integration'],
            ]);

            Log::channel('queue-messages')->info("Message added to stream $streamKey");

            // Dispatch a job to process this chat's stream
            ProcessTelegramMessageJob::dispatch($chatId);

            return response()->json(['status' => 'received'], 200);
        } catch (\Exception $e) {
            Log::channel('planfix-messages')->error("Webhook processing error: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
