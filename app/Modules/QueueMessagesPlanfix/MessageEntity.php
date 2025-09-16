<?php

namespace App\Modules\QueueMessagesPlanfix;

use App\Models\Message;
use App\Modules\PlanfixIntegration\PlanfixIntegrationEntity;
use App\Modules\TelegramAccount\TelegramAccountEntity;
use Carbon\Carbon;

class MessageEntity
{
    private const IN_PROGRESS = 'in_progress';
    const PENDING = 'pending';
    const COMPLETED = 'completed';
    const WAITING_RETRY = 'waiting_retry';
    const ERROR = 'error';
    private Message $message;
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public static function findByChatIdAndStatus(int $chatId, string $status)
    {
        $messages =  Message::where('chat_id', $chatId)
            ->where('status', $status)
            ->orderBy('created_at')
            ->get();


        return $messages->map(fn($message) => new self($message))->toArray();

    }

    public static function existsByChatIdAndStatus(int $chatId, string $status): bool
    {
        return Message::query()
            ->where('chat_id', $chatId)
            ->where('status', $status)
            ->exists();
    }

    public static function findFirstPendingByChatId(int $chatId): ?self
    {
        $message = Message::query()
            ->where('chat_id', $chatId)
            ->where('status', self::PENDING)
            ->orderBy('created_at')
            ->first();

        return $message ? new self($message) : null;
    }

    public static function findFirstWaitingRetryByChatId(int $chatId): ?self
    {
        $message = Message::query()
            ->where('chat_id', $chatId)
            ->where('status', self::WAITING_RETRY)
            ->where('next_retry_at', '<=', now())
            ->orderBy('next_retry_at')
            ->first();

        return $message ? new self($message) : null;

    }

    public static function existsWaitingRetryMessages(ChatEntity $chatEntity):bool
    {
        return Message::where('chat_id', $chatEntity->getId())
            ->where('status', self::WAITING_RETRY)
            ->exists();
    }

    public static function countSentMessagesForAccount(TelegramAccountEntity $accountEntity): int
    {
        return Message::where('token', PlanfixIntegrationEntity::findByTelegramAccountId($accountEntity->getId())->getToken())
            ->where('created_at', '>=', Carbon::now()->subMinute())
            ->count();
    }

    public static function countDuplicateMessagesLastMinute(TelegramAccountEntity $account): int
    {
        $token = PlanfixIntegrationEntity::findByTelegramAccountId($account->getId())->getToken();


        $rows = Message::query()
            ->select('chat_id', 'message')
            ->where('token', $token)
            ->where('created_at', '>=', now()->subMinute())
            ->get();

        $map = []; // normalized_message => ['count' => int, 'chats' => array of chat_id => true]

        foreach ($rows as $r) {
            $norm = self::normalizeMessage($r->message);
            if (!isset($map[$norm])) {
                $map[$norm] = ['count' => 0, 'chats' => []];
            }
            $map[$norm]['count'] += 1;
            $map[$norm]['chats'][$r->chat_id] = true;
        }

        $duplicates = 0;
        foreach ($map as $norm => $info) {
            $chatCount = count($info['chats']);
            if ($info['count'] >= 2 && $chatCount >= 2) {
                $duplicates += 1;
            }
        }

        return $duplicates;
    }

    private static function normalizeMessage(?string $message): string
    {
        if ($message === null) return '';

        // простая нормализация: lowercase, strip_tags, убираем пунктуацию, лишние пробелы
        $s = mb_strtolower(strip_tags($message));
        // удалить все знаки пунктуации и специальные символы
        $s = preg_replace('/[^\p{L}\p{N}\s]+/u', '', $s);
        // сжать пробелы
        $s = preg_replace('/\s+/u', ' ', $s);
        $s = trim($s);

        return $s;
    }

    public static function findById(int $messageId): MessageEntity
    {
        $message = Message::find($messageId);

        if (!$message) {
            throw new \Exception("Message with ID {$messageId} not found");
        }

        return new self($message);
    }

    public function findPreviousAccountMessageInOtherChat(): ?self
    {
        return Message::query()
            ->where('token', $this->getToken())
            ->where('chat_id', '!=', $this->getChatId())
            ->where('status', self::IN_PROGRESS)
            ->latest('id')
            ->first();
    }


    public function findProviderId(): string
    {
        $token = $this->getToken();
        $planfixIntegration = PlanfixIntegrationEntity::findByToken($token);
        return $planfixIntegration->getProviderId();
    }

    public function findChatNumberByChatId(): int
    {
        $chat = ChatEntity::getById($this->message->chat_id);

        return $chat->getChatId();
    }

    public static function existsInProgressMessages(ChatEntity $chatEntity):bool
    {
        return Message::where('chat_id',$chatEntity->getId())
            ->where('status',self::IN_PROGRESS)
            ->exists();
    }

    public static function changeStatusInProgressForToken(string $token, string $status): void
    {
       Message::where('token', $token)->where('status', self::IN_PROGRESS)
       ->update(['status' => $status]);
    }

    public static function getCompleteMessagesAll()
    {
        return Message::where('status', self::COMPLETED)->get();
    }


    public function updateStatus(string $status): bool
    {
        $this->message->status = $status;
        return $this->message->saveOrFail();
    }

    public static function setMessage (array $data): self
    {
        $message = Message::create([
            'chat_id' => $data['chat_id'],
            'token' => $data['token'],
            'message' => $data['message'],
            'attachments' => isset($data['attachments']) ? json_encode($data['attachments']) : null,
        ]);

        return new self($message);
    }

    public static function getMessageById(int $id): ?self
    {
        $message = Message::find($id);

        return $message ? new self($message) : null;
    }

    public function getModel(): Message
    {
        return $this->message;
    }

    public function getToken(): string
    {
        return  $this->message->token;
    }

    public function setStatusInProgress():void
    {
        $this->message->status = self::IN_PROGRESS;

        $this->message->saveOrFail();
    }

    public function setStatusCompleted():void
    {
        $this->message->status = self::COMPLETED;

        $this->message->saveOrFail();
    }


    public function setStatusError(string $errorMessage = null):void
    {
        $this->message->status = self::ERROR;
        $this->message->error_message = $errorMessage;

        $this->message->saveOrFail();
    }

    public function setStatusWaitingRetry(int $retryCount, Carbon $nextRetryAt):void
    {
        $this->message->status = self::WAITING_RETRY;
        $this->message->retry_count = $retryCount;
        $this->message->next_retry_at = $nextRetryAt;

        $this->message->saveOrFail();
    }

    public function delete(): void
    {
        $this->message->delete();
    }

    public function getRetryCount(): int
    {
        return $this->message->retry_count;
    }

    public function getChatId(): int
    {
        return  $this->message->chat_id;
    }

    public function setCalculatedDelay(int $baseDelay)
    {
        $this->message->base_delay = $baseDelay;

        $this->message->saveOrFail();
    }

    public function getId()
    {
        return $this->message->id;
    }


}
