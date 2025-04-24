<?php

namespace App\Modules\QueueMessagesPlanfix;

use App\Models\Message;
use App\Modules\PlanfixIntegration\PlanfixIntegrationEntity;

class MessageEntity
{
    private const IN_PROGRESS = 'in_progress';
    const PENDING = 'pending';
    const COMPLETED = 'completed';

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

    public function findProviderId(): string
    {
        $token = $this->getToken();
        $providerId = PlanfixIntegrationEntity::getToken($token);
        return $providerId;
    }

    public static function existsInProgressMessages(ChatEntity $chatEntity):bool
    {
        return Message::where('chat_id',$chatEntity->getId())
            ->where('status',self::IN_PROGRESS)
            ->exists();
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

    public function delete(): void
    {
        $this->message->delete();
    }

}
