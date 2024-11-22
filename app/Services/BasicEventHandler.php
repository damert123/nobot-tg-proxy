<?php
declare(strict_types=1);
namespace App\Services;


use Carbon\Carbon;
use danog\MadelineProto\API;
use danog\MadelineProto\SimpleEventHandler;
use Illuminate\Support\Facades\Log;

class BasicEventHandler extends SimpleEventHandler
{



    public function onUpdateNewMessage(array $update): void
    {
        // Проверяем, есть ли ключ 'message' в обновлении
        $message = $update['message'] ?? null;




        if ($message) {

            $text = $message['message'] ?? 'Без текста';

            $fromId = $message['from_id'] ?? null;
            $userInfo = $this->getInfo($fromId);

            $date = $message['date'];
            $formatDate = Carbon::createFromTimestamp($date, 'Europe/Moscow')->toDateTimeString();

            $photoId = $userInfo['User']['photo']['photo_id'] ?? null;
            $dcId = $userInfo['User']['photo']['dc_id'] ?? null;
            $userFirstName = $userInfo['User']['first_name'] ?? '';
            $userLastName = $userInfo['User']['last_name'] ?? '';
            $userName = $userInfo['User']['username'] ?? '';
            $userId = $userInfo['User']['id'] ?? '';




            Log::channel('tg-messages')->info("Новое сообщение: {$text}, от пользователя: {$fromId}, username: {$userName}, имя: {$userFirstName}, фамилия: {$userLastName} Дата сообщения: {$formatDate}");

            $pathAvatar = __DIR__ . '/downloads/avatars/';
            $filePathMediaPhoto = __DIR__ . '/downloads/media/photo/';
            $filePathMediaVoice = __DIR__ . '/downloads/media/voice/';
            $filePathMediaDocument = __DIR__ . '/downloads/media/document/';
            $filePathMediaRoundMessage = __DIR__ . '/downloads/media/video_message/';

            if (isset($message['media'])){
                try {
                    $media = $message['media'];

                    if (isset($media['photo'])) {
                        $photoId = $media['photo']['id'];
                        if (!is_dir($filePathMediaPhoto)){
                            mkdir($filePathMediaPhoto, 0755, true);
                        }

                        Log::info('Получено фото');
                        $this->downloadToFile($media, $filePathMediaPhoto . "{$photoId}" . time() . ".jpg");
                    } elseif (isset($media['voice']) && $media['voice'] === true) {
                        $voiceId = $media['document']['id'];
                        if (!is_dir($filePathMediaVoice)){
                            mkdir($filePathMediaVoice, 0755, true);
                        }

                        Log::info('Получено голосовое сообщение');
                        $this->downloadToFile($media, $filePathMediaVoice . "{$voiceId}_" . time() . ".ogg");
                    }elseif (isset($media['round']) && $media['round'] === true){
                        $roundId = $media['document']['id'];
                        if (!is_dir($filePathMediaRoundMessage)){
                            mkdir($filePathMediaRoundMessage, 0755, true);
                        }
                        Log::info('Получено видеосообщение');
                        $this->downloadToFile($media, $filePathMediaRoundMessage . "{$roundId}.mp4");


                    } elseif (isset($media['document'])) {
                        $documentId = $media['document']['id'];
                        if (!is_dir($filePathMediaDocument)){
                            mkdir($filePathMediaDocument, 0755, true);
                        }

                        Log::info('Получен документ');
                        $this->downloadToFile($media, $filePathMediaDocument . "{$documentId}");

                    }

//                    Log::channel('tg-messages')->info("Информация о медиа:" . json_encode($media, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
                catch (\Throwable $e){
                    Log::channel('tg-messages')->info("Медиа в сообщении отсутствует");
                }
            }

//            if ($photoId) {
//                // Путь для сохранения
//
//                try {
//                    $photoInfo = $this->getPropicInfo($message);
//
//                    $this->downloadToFile($photoInfo, $pathAvatar . "{$userId}.jpg");
//                    Log::channel('tg-messages')->info("Фото профиля пользователя сохранено в {$pathAvatar} {$userId}.jpg");
//                   Log::channel('tg-messages')->info("Информация о фото пользователя:" . json_encode($photoInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
//
//                } catch (\Throwable $e) {
//
//                    Log::channel('tg-messages')->error("Ошибка при сохранении фото профиля: {$e->getMessage()}");
//                }
//            } else {
//                Log::channel('tg-messages')->info("Фото профиля пользователя отсутствует");
//            }




//            Log::channel('tg-messages')->info("Информация о пользователе:" . json_encode($userInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
//            Log::channel('tg-messages')->info("Полная информация: " . json_encode($update, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        } else {
            Log::channel('tg-messages')->warning('Обновление без сообщения');
        }
    }

}
