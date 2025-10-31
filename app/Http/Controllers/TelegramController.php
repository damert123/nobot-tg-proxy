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
            // ДОБАВЛЯЕМ: Логируем все детали запроса
            Log::channel('top-up-messages')->info('Barzha webhook headers:', ['content-type' => $request->header('Content-Type')]);
            Log::channel('top-up-messages')->info('Barzha webhook raw content:', ['raw' => $request->getContent()]);
            Log::channel('top-up-messages')->info('Barzha webhook parsed data:', $request->all());

            $requestData = $request->all();


            $contentType = $request->header('Content-Type');
            if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
                $rawData = $request->getContent();
                parse_str($rawData, $formData);

                Log::channel('top-up-messages')->info('Parsed form data:', $formData);


                $requestData = $formData;
            }

            $messageData = $this->extractMessageData($requestData);


            Log::channel('top-up-messages')->info('Extracted message data:', $messageData);

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
                    SendTelegramMessageJob::dispatch($telegramIdFrom, $data->message, $data->toId)->onQueue('tg-service-messages');
                } else {
                    Log::channel('top-up-messages')->warning("Не найден ни telegram_link, ни task.");
                    $status = 'invalid';
                }
            }

            return response()->json([
                'status' => $status,
            ]);

        } catch (\Exception $e) {
            Log::channel('top-up-messages')->error("Ошибка обработки вебхука: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }


    public function sendMessageNoticeNewRegister(Request $request)
    {
        try {
            Log::channel('new-register-messages')->info('Barzha webhook received:', $request->all());

            $data = TelegramMessageDTO::fromArray($request->all());

            $telegramIdFrom = $data->fromId;
            $status = 'skipped';

            if (!empty($data->message)) {

                if (!empty($data->toId)) {
                    $this->queueMessage($telegramIdFrom, $data->message, $data->toId);
                }
                else {
                    Log::channel('new-register-messages')->warning("Не найден ни telegram_link, ни task.");
                    $status = 'invalid';
                }
            }

            return response()->json([
                'status' => $status,
            ]);

        }catch (\Exception $e){
            Log::channel('new-register-messages')->error("Ошибка обработки вебхука: {$e->getMessage()}");
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
            $data = $requestData;
        } else {
            $key = array_keys($requestData)[0] ?? '';
            $data = json_decode($key, true) ?? [];
        }


        if (isset($data['message'])) {
            $data['message'] = $this->normalizeMessage($data['message']);
        }

        return $data;
    }

    private function normalizeMessage(string $message): string
    {
        $message = urldecode($message);

        $message = str_replace('__', "\n\n", $message);

        $message = str_replace('_', ' ', $message);

        $message = preg_replace('/[ \t]+/', ' ', $message);

        $message = preg_replace('/^[ \t]+|[ \t]+$/m', '', $message);

        return trim($message);
    }
}
