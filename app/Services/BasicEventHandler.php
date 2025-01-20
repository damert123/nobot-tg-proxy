<?php
declare(strict_types=1);
namespace App\Services;


use Carbon\Carbon;
use danog\MadelineProto\API;
use danog\MadelineProto\SimpleEventHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BasicEventHandler extends SimpleEventHandler
{
    public function onUpdateNewMessage(array $update): void
    {
//        $this->setReportPeers(406210384);

        $message = $update['message'] ?? null;

        if ($message) {
            Log::channel('tg-messages')->info("Полная информация: " . json_encode($update, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));


            $text = $message['message'] ?? 'Без текста';
            $peerId = $message['peer_id'] ?? null;
            $fromId = $message['from_id'] ?? null;

            // Получаем данные текущего пользователя (менеджера)
            $self = $this->getSelf();
            $managerId = $self['id'];

            Log::channel('tg-messages')->info("Получено новое сообщение", [
                'peer_id' => $peerId,
                'from_id' => $fromId,
                'manager_id' => $managerId,
                'message' => $text,
            ]);
            Log::channel('tg-messages')->info("$managerId Пришло от этой сессии");

            // Если `from_id` и `peer_id` совпадают, меняем `peer_id` на ID менеджера
            if ($fromId === $peerId) {
                Log::channel('tg-messages')->info("from_id и peer_id совпадают. Заменяем peer_id на managerId.");
                $peerId = $managerId;
                Log::channel('tg-messages')->info("Коррекция peer_id: совпадение с from_id", [
                    'from_id' => $fromId,
                    'peer_id' => $peerId,
                    'new_peer_id' => $managerId,
                ]);
            }

            // Определяем, кто отправитель
            $isManagerSender = ($fromId === $managerId);
            $clientId = $isManagerSender ? $peerId : $fromId;

            // Определяем, какой ID использовать для поиска аккаунта Telegram
            $telegramAccountId = $isManagerSender ? $fromId : $peerId;

            // Получаем информацию о клиенте
            $clientInfo = $this->getInfo($clientId);
            $clientFirstName = $clientInfo['User']['first_name'] ?? '';
            $clientLastName = $clientInfo['User']['last_name'] ?? '';
            $clientUserName = $clientInfo['User']['username'] ?? '';

            Log::channel('tg-messages')->info("Информация о клиенте", [
                'client_id' => $clientId,
                'first_name' => $clientFirstName,
                'last_name' => $clientLastName,
                'username' => $clientUserName,
            ]);

            // Получаем информацию об отправителе
            $senderInfo = $this->getInfo($fromId);
            $senderFirstName = $senderInfo['User']['first_name'] ?? '';
            $senderLastName = $senderInfo['User']['last_name'] ?? '';
            $senderUserName = $senderInfo['User']['username'] ?? '';

            Log::channel('tg-messages')->info("Информация об отправителе", [
                'sender_id' => $fromId,
                'first_name' => $senderFirstName,
                'last_name' => $senderLastName,
                'username' => $senderUserName,
            ]);

            // Находим Telegram аккаунт по peerId
            $telegramAccount = DB::table('telegram_accounts')
                ->where('telegram_id', $telegramAccountId)
                ->first();

            if (!$telegramAccount) {
                Log::channel('tg-messages')->warning("Не удалось найти Telegram аккаунт (пришло сообщение из бесед) с ID: $telegramAccountId");
                return;
            }

            $planfixIntegration = DB::table('planfix_integrations')
                ->where('telegram_account_id', $telegramAccount->id)
                ->first();

            if (!$planfixIntegration) {
                Log::warning("Не удалось найти Planfix интеграцию для аккаунта ID: $peerId");
                return;
            }

            $telegramProfileLink = $senderUserName
                ? "https://t.me/$senderUserName"
                : "https://t.me/$fromId";

            $clientTelegramProfileLink = $clientUserName
                ? "https://t.me/$clientUserName"
                : "https://t.me/$clientId";

            $telegramDataProfileLink = $senderUserName
                ? "https://t.me/$senderUserName"
                : "ChatId:{$fromId}";

            $clientDataTelegramProfileLink = $clientUserName
                ? "https://t.me/$clientUserName"
                : "ChatId:{$clientId}";

            Log::channel('tg-messages')->info($telegramProfileLink);

            // Получаем задачу из Planfix
            $dataGetTask = [
                'cmd' => 'getTask',
                'providerId' => $planfixIntegration->provider_id,
                'planfix_token' => $planfixIntegration->planfix_token,
                'chatId' => $clientId,
            ];

            $responseGetTask = Http::asForm()->post('https://agencylemon.planfix.ru/webchat/api', $dataGetTask);

            if ($responseGetTask->successful() && !empty($responseGetTask->json())) {
                Log::channel('planfix-messages')->info('ТАСКА УСПЕШНО ПОЛУЧЕНА', [
                    'response' => $responseGetTask->json(),
                ]);

                $data = [
                    'cmd' => 'newMessage',
                    'providerId' => $planfixIntegration->provider_id,
                    'chatId' => $clientId,
                    'planfix_token' => $planfixIntegration->planfix_token,
                    'message' => $text ?: 'Файл',
                    'title' => $clientFirstName . ' ' . $clientLastName,
                    'contactId' => $fromId,
                    'contactName' => $senderFirstName,
                    'contactLastName' => $senderLastName,
                    'telegramUserName' => "$telegramProfileLink",
                    'contactData' => "Telegram {$telegramDataProfileLink}"
                ];
            } else {
                Log::channel('planfix-messages')->info('ТАСКА НЕ НАЙДЕНА. СОЗДАЁМ НОВУЮ', [
                    'response' => $responseGetTask->json(),
                ]);
                Log::channel('planfix-messages')->info("Создаётся новая задача", [
                    'client_id' => $clientId,
                    'contact_name' => $clientFirstName,
                    'message' => $text,
                ]);

                // При создании новой задачи устанавливаем клиента как отправителя
                $data = [
                    'cmd' => 'newMessage',
                    'providerId' => $planfixIntegration->provider_id,
                    'chatId' => $clientId,
                    'planfix_token' => $planfixIntegration->planfix_token,
                    'message' => $text ?: 'Файл',
                    'title' => $clientFirstName . ' ' . $clientLastName,
                    'contactId' => $clientId, // Используем clientId для нового сообщения
                    'contactName' => $clientFirstName,
                    'contactLastName' => $clientLastName,
                    'telegramUserName' => "$clientTelegramProfileLink",
                    'contactData' => "Telegram {$clientDataTelegramProfileLink}"
                ];
            }

            // Логируем финальное сообщение
            Log::channel('tg-messages')->info("Новое сообщение: {$text}, от пользователя: {$fromId}, username: {$clientUserName}, имя: {$senderFirstName}, фамилия: {$senderLastName}");

            if (isset($message['media'])){
                try {

                    $media = $message['media'];

                    $mediaId = null;
                    foreach ($media as $key => $value) {
                        if (is_array($value) && isset($value['id'])) {
                            $mediaId = $value['id'];
                            break;
                        }
                    }

                    // Если ID найден, проверяем его в БД
                    if ($mediaId !== null) {
                        $idMessageIgnore = DB::table('id_message_to_tg_telegram')->where('message_id', $mediaId)->exists();

                        if ($idMessageIgnore) {
                            DB::table('id_message_to_tg_telegram')->where('message_id', $mediaId)->delete();
                            return; // Пропускаем обработку, если ID уже есть в БД
                        }
                    }

                    if (isset($media['photo'])) {
                        $photoId = $media['photo']['id'];

                        $filePath = "telegram/media/photo/{$photoId}.jpg";

                        if (!Storage::disk('public')->exists('telegram/media/photo')){
                            Storage::disk('public')->makeDirectory('telegram/media/photo');
                        }


                        Log::info('Получено фото');
                        $this->downloadToFile($media, Storage::disk('public')->path($filePath));

                        $publicUrl = url(Storage::url($filePath));

                        $data['attachments[name]'] = 'photo.jpg';
                        $data['attachments[url]'] = $publicUrl;

                        Log::channel('tg-messages')->info('Фото успешно сохранено и ссылка сгенерирована', ['url' => $publicUrl]);


                    } elseif (isset($media['voice']) && $media['voice'] === true) {



                        $voiceId = $media['document']['id'];

                        $filePath = "telegram/media/voice/{$voiceId}.ogg";

                        if (!Storage::disk('public')->exists('telegram/media/voice')){
                            Storage::disk('public')->makeDirectory('telegram/media/voice');
                        }

                        Log::info('Получено голосовое сообщение');
                        $this->downloadToFile($media, Storage::disk('public')->path($filePath));

                        $publicUrl = url(Storage::url($filePath));

                        $data['attachments[name]'] = 'voice.ogg';
                        $data['attachments[url]'] = $publicUrl;

                        Log::channel('tg-messages')->info('ГОЛОСОВОЕ успешно сохранено и ссылка сгенерирована', ['url' => $publicUrl]);


                    } elseif (isset($media['video']) && $media['video'] === true){
                        $videoId = $media['document']['id'];

                        $videoSize = $media['document']['size'];

                        $filePath = "telegram/media/video/{$videoId}.mp4";

                        if (!Storage::disk('public')->exists('telegram/media/video')){
                            Storage::disk('public')->makeDirectory('telegram/media/video');
                        }

                        if ($videoSize > 20 * 1024 * 1024) { // Проверка на размер (50 МБ)
                            Log::info('Получено большое видео, оно не будет сохранено.');
                            $textMessage = $data['message'] ?: 'Файл';
                            $data['message'] = $textMessage . "\n(⚠️ Вам отправлено большое видео, смотрите в Телеграмме)";
                        } else {
                            Log::info('Получено видео');
                            $this->downloadToFile($media, Storage::disk('public')->path($filePath));

                            $publicUrl = url(Storage::url($filePath));

                            $data['attachments[name]'] = 'video.mp4';
                            $data['attachments[url]'] = $publicUrl;

                            Log::channel('tg-messages')->info('ВИДЕО успешно сохранено и ссылка сгенерирована', ['url' => $publicUrl]);
                        }

                    } elseif (isset($media['round']) && $media['round'] === true){
                        $roundId = $media['document']['id'];


                        $filePath = "telegram/media/round/{$roundId}.mp4";

                        if (!Storage::disk('public')->exists('telegram/media/round')){
                            Storage::disk('public')->makeDirectory('telegram/media/round');
                        }

                        Log::info('Получено видеосообщение');
                        $this->downloadToFile($media, Storage::disk('public')->path($filePath));

                        $publicUrl = url(Storage::url($filePath));

                        $data['attachments[name]'] = 'videoMessage.mp4';
                        $data['attachments[url]'] = $publicUrl;

                        Log::channel('tg-messages')->info('КРУЖОК успешно сохранено и ссылка сгенерирована', ['url' => $publicUrl]);



                    } elseif (isset($media['document'])) {
                        $document = $media['document'];


                        if ($document['mime_type'] === 'application/pdf') {
                            $documentId = $document['id'];
                            $filePath = "telegram/media/document/{$documentId}.pdf";

                            // Создаем директорию, если ее нет
                            if (!Storage::disk('public')->exists('telegram/media/document')) {
                                Storage::disk('public')->makeDirectory('telegram/media/document');
                            }

                            Log::info('Получен PDF-документ');
                            $this->downloadToFile($media, Storage::disk('public')->path($filePath));

                            $publicUrl = url(Storage::url($filePath));

                            $data['attachments[name]'] = 'document.pdf';
                            $data['attachments[url]'] = $publicUrl;

                            Log::channel('tg-messages')->info('PDF-документ успешно сохранен и ссылка сгенерирована', ['url' => $publicUrl]);
                        } else {
                            // Логируем и игнорируем другие типы документов
                            Log::info("Документ с MIME-типом {$document['mime_type']} пропущен.");
                            return;
                        }


                    }

//                    Log::channel('tg-messages')->info("Информация о медиа:" . json_encode($media, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
                catch (\Throwable $e){
                    Log::channel('tg-messages')->info("Медиа в сообщении отсутствует");
                }
            }


            try {
                if (!empty($message['entities'])) {
                    foreach ($message['entities'] as $entity) {
                        if ($entity['_'] === 'messageEntityTextUrl' && $entity['url'] === 'planfix://internal') {
                            Log::channel('planfix-messages')->info('Это сообщение из CRM, отправка в Planfix пропущена.', [
                                'message' => $message,
                                $update
                            ]);
                            return;
                        }
                    }
                }

                if ($message['id'] != null){
                    $idTextMessageIgnore = DB::table('id_message_to_tg_telegram')->where('message_id', $message['id'])->exists();

                    if ($idTextMessageIgnore) {
                        DB::table('id_message_to_tg_telegram')->where('message_id', $message['id'])->delete();
                        return;
                    }
                }


                if (strpos($message['message'] ?? '', "\u{200B}") !== false) {
                    Log::channel('planfix-messages')->info('Это сообщение из CRM, отправка в Planfix пропущена.', [
                        'message' => $message,
                        'update' => $update,
                    ]);
                    return; // Пропускаем отправку
                }


                $response = Http::asForm()->post('https://agencylemon.planfix.ru/webchat/api', $data);

                if ($response->successful()){
                    Log::channel('planfix-messages')->info('Сообщение успешно отправлено в PlanFix', [
                        'response' => $response->json(),
                    ]);

//                    if (!empty($filePath)) {
//                        if (Storage::disk('public')->exists($filePath)) {
//                            Storage::disk('public')->delete($filePath);
//                            Log::channel('planfix-messages')->info("Файл успешно удален: $filePath");
//                        } else {
//                            Log::channel('planfix-messages')->warning("Файл для удаления не найден: $filePath");
//                        }
//                    }

                }

                else {
                    Log::channel('planfix-messages')->warning('Ошибка при отправке сообщения в Planfix', [
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]);

                }
            }catch (\Throwable $e){
                Log::channel('planfix-messages')->error('Не удалось отправить сообщение в Planfix', [
                    'error' => $e->getMessage(),
                ]);
            }

//            Log::channel('tg-messages')->info("Информация о пользователе:" . json_encode($userInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            Log::channel('tg-messages')->info("Обработка сообщения завершена", [
                'client_id' => $clientId,
                'from_id' => $fromId,
                'task_found' => $responseGetTask->successful(),
                'final_message_data' => $data,
            ]);

        } else {
            Log::channel('tg-messages')->warning('Обновление без сообщения');
        }
    }




}
