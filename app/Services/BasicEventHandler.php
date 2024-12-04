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
            Log::channel('tg-messages')->info($self['id'] . "Пришло от этой сессии");

            $managerId = $self['id'];

            if ($fromId === $peerId) {
                Log::channel('tg-messages')->info("from_id и peer_id совпадают. Заменяем peer_id на managerId.");
                $peerId = $managerId; // Меняем peer_id на managerId
            }

            // Определяем, кто клиент
            $isManagerSender = ($fromId === $managerId);
            $clientId = $isManagerSender ? $peerId : $fromId;

            // Определяем, какой ID использовать для поиска аккаунта Telegram
            // Если пишет менеджер, то ищем по from_id (ID менеджера)
            // Если пишет клиент, то ищем по peer_id (ID менеджера)
            $telegramAccountId = $isManagerSender ? $fromId : $peerId;

            // Получаем информацию о клиенте
            $clientInfo = $this->getInfo($clientId);
            $clientFirstName = $clientInfo['User']['first_name'] ?? '';
            $clientLastName = $clientInfo['User']['last_name'] ?? '';
            $clientUserName = $clientInfo['User']['username'] ?? '';

            // Получаем информацию об отправителе
            $senderInfo = $this->getInfo($fromId);
            $senderFirstName = $senderInfo['User']['first_name'] ?? '';
            $senderLastName = $senderInfo['User']['last_name'] ?? '';
            $senderUserName = $senderInfo['User']['username'] ?? '';



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
                : 'Telegram профиль недоступен';
            Log::channel('tg-messages')->info($telegramProfileLink);


            // Данные для CRM
            $data = [
                'cmd' => 'newMessage',
                'providerId' => $planfixIntegration->provider_id, // Уникальный идентификатор системы
                'chatId' => $clientId, // Уникальный ID чата (всегда ID клиента)
                'planfix_token' => $planfixIntegration->planfix_token, // Токен, указывается в .env
                'message' => $text ?: 'Файл', // Текст сообщения
                'title' => $clientFirstName, // Заголовок задачи (всегда имя клиента)
                'contactId' => $fromId, // ID отправителя
                'contactName' => $senderFirstName, // Имя отправителя
                'contactLastName' => $senderLastName, // Фамилия отправителя (необязательно)
                'contactData' => "Telegram: $telegramProfileLink",
            ];

            Log::channel('tg-messages')->info("Новое сообщение: {$text}, от пользователя: {$fromId}, username: {$clientUserName}, имя: {$senderFirstName}, фамилия: {$senderLastName}");

            if (isset($message['media'])){
                try {
                    $media = $message['media'];

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

                        if ($videoSize > 50 * 1024 * 1024) { // Проверка на размер (50 МБ)
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
                        $documentId = $media['document']['id'];

                        $filePath = "telegram/media/document/{$documentId}.txt";

                        if (!Storage::disk('public')->exists('telegram/media/document')){
                            Storage::disk('public')->makeDirectory('telegram/media/document');
                        }

                        Log::info('Получен документ');
                        $this->downloadToFile($media, Storage::disk('public')->path($filePath));

                        $publicUrl = url(Storage::url($filePath));

                        $data['attachments[name]'] = 'document.txt';
                        $data['attachments[url]'] = $publicUrl;

                        Log::channel('tg-messages')->info('ДОКУМЕНТ успешно сохранено и ссылка сгенерирована', ['url' => $publicUrl]);


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


                if (strpos($message['message'] ?? '', "\u{200B}") !== false) {
                    Log::channel('planfix-messages')->info('Это сообщение из CRM, отправка в Planfix пропущена.', [
                        'message' => $message,
                        'update' => $update,
                    ]);
                    return; // Пропускаем отправку
                }


                $response = Http::asForm()->post('https://testservice123.planfix.ru/webchat/api', $data);

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
            Log::channel('tg-messages')->info("Полная информация: " . json_encode($update, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        } else {
            Log::channel('tg-messages')->warning('Обновление без сообщения');
        }
    }




}
