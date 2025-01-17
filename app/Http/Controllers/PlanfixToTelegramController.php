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

            $queueKey = "queue:chat:$chatId";
            $lockKey = "lock:chat:$chatId";


            $res = Redis::command('RPUSH', [$queueKey, json_encode($data)]);

            Log::channel('queue-messages')->warning("DATA $res");


            // Если нет блокировки, запускаем обработку очереди
            if (!Redis::command('exists', [$lockKey])) {
                ProcessTelegramMessageJob::dispatch($chatId);
            }

            return response()->json(['status' => 'received'], 200);
        } catch (\Exception $e) {
            Log::channel('planfix-messages')->error("Ошибка обработки вебхука: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 400);
        }

    }
}
