<?php

namespace App\Http\Controllers;

use App\DTO\TelegramMessageDTO;
use App\Jobs\SendTelegramMessageJob;
use App\Modules\QueueServiceMessagesToTelegram\QueueServiceMessagesEntity;
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

                if (!empty($data->toId)) {
                    $this->queueMessage($telegramIdFrom, $data->message, $data->toId);
//                    $status = $this->topUpSendMessageService->sendMessageTopUpDirectly($telegramIdFrom, $data->message, $data->toId);
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


    public function sendMessageWithoutRecent(Request $request)
    {

        try {
            Log::channel('top-up-messages')->info('Barzha webhook received:', $request->all());

            $requestData = $request->all();


            $messageData = $this->extractMessageData($requestData);

            $validated = validator($messageData, [
                'from_id' => ['required', 'integer'],
                'to_id'   => ['nullable', 'string'],
                'task'    => ['nullable', 'string'],
                'message' => ['required', 'string'],
            ])->validate();

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

    private function queueMessage(int $telegramId, string $message, string $telegramLink): void
    {
        $lastScheduled = QueueServiceMessagesEntity::getLastScheduledAt();

        $scheduledAt = now();

        if ($lastScheduled && $lastScheduled->getScheduledAt()->gt(now())){
            $scheduledAt = $lastScheduled->getNextAvailableTime();
        }

        QueueServiceMessagesEntity::create($telegramId, $message, $telegramLink, $scheduledAt, QueueServiceMessagesEntity::STATUS_PENDING);

        Log::channel('top-up-messages')->info("Сообщение добавлено в очередь", [
            'telegram_id' => $telegramId,
            'scheduled_at' => $scheduledAt
        ]);
    }

    private function extractMessageData(array $requestData): array
    {
        if (isset($requestData['from_id'])) {
            return $requestData;
        }

        foreach ($requestData as $key => $value){
            if (is_string($key)){
                $decoded = json_decode($key, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($decoded['from_id'])){
                    return $decoded;
                }
            }
        }

        throw new \Exception('Could not extract message data the from request');

    }
}
