<?php

namespace App\Http\Controllers;

use App\DTO\TelegramMessageDTO;
use App\Jobs\SendTelegramMessageJob;
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
            $status = 'skipped';

            if (!empty($data->message)) {
                // Если есть telegram_link (to_id), отправляем сразу
                if (!empty($data->toId)) {
                    $status = $this->topUpSendMessageService->sendMessageTopUpDirectly($telegramIdFrom, $data->message, $data->toId);
                }
                // Если нет telegram_link, но есть task, тогда идем в CRM
                elseif (!empty($data->task)) {
                    $status = $this->topUpSendMessageService->sendMessageTopUpTask($telegramIdFrom, $data->message, $data->task);
                } else {
                    Log::channel('top-up-messages')->warning("Не найден ни telegram_link, ни task.");
                    $status = 'invalid';
                }
            }

            return response()->json([
                'status' => $status,
            ]);

        }catch (\Exception $e){
            Log::channel('top-up-messages')->error("Ошибка обработки вебхука: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 400);
        }

    }


    public function sendMessageWithoutRecent(Request $request)
    {

        try {
            Log::channel('top-up-messages')->info('Barzha webhook received:', $request->all());


            $validated = $request->validate([
                'from_id' => ['required', 'integer'],
                'to_id'   => ['nullable', 'string'],
                'task'    => ['nullable', 'string'],
                'message' => ['required', 'string'],
            ]);

            $data = TelegramMessageDTO::fromArray($validated);

            $telegramIdFrom = $data->fromId;
            $status = 'sent';

            if (!empty($data->message)) {

                if (!empty($data->toId)) {
                    SendTelegramMessageJob::dispatch($telegramIdFrom, $data->message, $data->toId)->onQueue('tg-service-messages');;
//                    $status = $this->topUpSendMessageService->sendMessageDirectly($telegramIdFrom, $data->message, $data->toId);
                }
                else {
                    Log::channel('top-up-messages')->warning("Не найден ни telegram_link, ни task.");
                    $status = 'invalid';
                }
            }

            return response()->json([
                'status' => $status,
            ]);

        }catch (\Exception $e){
            Log::channel('top-up-messages')->error("Ошибка обработки вебхука: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
