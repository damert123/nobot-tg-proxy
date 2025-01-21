<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTelegramMessageJob;
use App\Modules\QueueMessagesPlanfix\ChatEntity;
use App\Modules\QueueMessagesPlanfix\MessageEntity;
use App\Services\PlanfixService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

            // Убедитесь, что chatId существует
            if (!$chatId) {
                throw new \Exception('chatId is required');
            }

            $chat = ChatEntity::setChat($chatId);

            $message = MessageEntity::setMessage([
                'chat_id' => $chat->getId(),
                $data
            ]);

            Log::channel('planfix-messages')->info('СООБЩЕНИЕ УСПЕШНО ПОЛУЧЕНО:', [
                'chat' => $chat->getModel()->toArray(),
                'message' => $message->getModel()->toArray(),
            ]);


            return response()->json(['status' => 'received'], 200);
        }catch (\Exception $e){
            Log::channel('planfix-messages')->error("Ошибка обработки вебхука: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 400);
        }

    }
}
