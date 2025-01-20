<?php

namespace App\Jobs;

use App\Models\MessagePlanfix;
use App\Services\PlanfixService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Predis\Command\Redis\XADD;
use Predis\Command\Redis\XREAD;

class ProcessTelegramMessageJob implements ShouldQueue
{
    use Queueable;

    protected $chatId;

    public function __construct(int $chatId)
    {
        $this->chatId = $chatId;
    }

    public function handle()
    {
        $message = MessagePlanfix::where('chat_id', $this->chatId)
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->lockForUpdate()
            ->first();

        if (!$message) {
            return; // Если сообщений нет, выходим
        }

        try {
            // Инициализация и отправка сообщения
            $this->processMessage($message);

            // Обновляем статус на completed
            $message->update(['status' => 'completed']);

            // Добавляем следующее сообщение в очередь
            $nextMessage = MessagePlanfix::where('chat_id', $this->chatId)
                ->where('status', 'pending')
                ->orderBy('created_at')
                ->first();

            if ($nextMessage) {
                self::dispatch($this->chatId);
            }
        } catch (\Exception $e) {
            // Обновляем статус на failed в случае ошибки
            $message->update(['status' => 'failed']);
            Log::error("Ошибка обработки сообщения для чата {$this->chatId}: {$e->getMessage()}");
        }
    }

    protected function processMessage(MessagePlanfix $message): void
    {
        try {
            $planfixService = app(PlanfixService::class);
            $telegramAccount = $planfixService->getIntegrationAndAccount($message->token);
            $madelineProto = $planfixService->initializeModelineProto($telegramAccount->session_path);

            // Отправка сообщения
            if (!empty($message->message)) {
                $planfixService->sendMessage($madelineProto, $message->chat_id, $message->message);
            }

            // Отправка вложений
            if (!empty($message->attachments)) {
                $planfixService->sendAttachment($madelineProto, $message->chat_id, $message->attachments, $message->message);
            }
        } catch (\Exception $e) {
            throw new \Exception("Ошибка отправки сообщения: {$e->getMessage()}");
        }
    }
}
