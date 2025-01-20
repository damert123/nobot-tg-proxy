<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTelegramMessageJob;
use App\Models\MessagePlanfix;
use App\Services\PlanfixService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Predis\Command\Redis\XREAD;
use Predis\Command\RedisFactory;

class PlanfixToTelegramController extends Controller
{
    protected PlanfixService $planfixService;
    protected Redis $redis;

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

            $streamKey = "chat:{$data['chatId']}";

            $this->redis->command('XADD', [['*'], [
                'chat_id' => $data['chatId'],
                'token' => $data['token'],
                'message' => $data['message'] ?? null,
                'attachments' => json_encode($data['attachments'] ?? [])
                ]
            ]);

            return response()->json(['status' => 'received'], 200);
        } catch (\Exception $e) {
            Log::channel('planfix-messages')->error("Webhook processing error: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
