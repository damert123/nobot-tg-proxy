<?php

namespace App\Modules\PlanfixNotification;


use App\Models\NotificationPlanfix;

class NotificationEntity
{


    public const TYPE_PEER_FLOOD = 'PEER_FLOOD (клиент перестал отвечать на соообщения)';
    public const PEER_FLOOD_MESSAGE =
        "[⚠️ Уведомление] Доставка сообщения не удалась: клиент долго не отвечает на сообщения \n\n"
        . "📩 Пожалуйста, отправьте сообщение вручную через Telegram.";

    public const CONTACT_ID = 777777777;
    public const CONTACT_NAME = '⚠️ Бот Уведомлений ⚠️';
    public const CONTACT_LAST_NAME = '';

    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAIL    = 'fail_http';
    public const STATUS_ERROR   = 'error';

    private NotificationPlanfix $notificationPlanfix;

    public function __construct(NotificationPlanfix $notificationPlanfix)
    {
        $this->notificationPlanfix = $notificationPlanfix;
    }

    public static function buildPayloadForPeerFlood(
        string $planfixToken,
        int $chatId,
        string $providerId,
        string $typeNotification = self::PEER_FLOOD_MESSAGE
    ): array
    {
        return  [
            'cmd'             => 'newMessage',
            'providerId'      => $providerId,
            'chatId'          => $chatId,
            'planfix_token'   => $planfixToken,
            'message'         => $typeNotification,
            'contactId'       => self::CONTACT_ID,
            'contactName'     => self::CONTACT_NAME,
            'contactLastName' => self::CONTACT_LAST_NAME,
        ];

    }


    public static function create(
        int $chatId,
        string $providerId,
        string $typeNotification,
        string $status = 'unknown'
    )
    {
        NotificationPlanfix::create([
            'chat_id' => $chatId,
            'provider_id' => $providerId,
            'type_notification' => $typeNotification,
            'status' => $status,
        ]);

    }

}
