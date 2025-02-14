<?php

namespace App\Http\Controllers;

use App\DTO\TelegramMessageDTO;
use App\Services\TopUpSendMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    protected TopUpSendMessageService $topUpSendMessageService;

    public function __construct(TopUpSendMessageService $topUpSendMessageService)
    {
        $this->topUpSendMessageService = $topUpSendMessageService;
    }

    public function handle(Request $request)
    {
        try {
            Log::channel('top-up-messages')->info('Barzha webhook received:', $request->all());

            $data = TelegramMessageDTO::fromArray($request->all());

            $telegramIdFrom = $data->fromId;

            if (!empty($data->message)) {
                // Если есть telegram_link (to_id), отправляем сразу
                if (!empty($data->toId)) {
                    $this->topUpSendMessageService->sendMessageTopUpDirectly($telegramIdFrom, $data->message, $data->toId);
                }
                // Если нет telegram_link, но есть task, тогда идем в CRM
                elseif (!empty($data->task)) {
                    $this->topUpSendMessageService->sendMessageTopUpTask($telegramIdFrom, $data->message, $data->task);
                } else {
                    Log::channel('top-up-messages')->warning("Не найден ни telegram_link, ни task.");
                }
            }

            return response()->json(["message" => "Send Message complete"]);

        }catch (\Exception $e){
            Log::channel('top-up-messages')->error("Ошибка обработки вебхука: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 400);
        }

    }
}
