<?php

namespace App\Http\Controllers;

use App\Models\TelegramAccount;
use App\Modules\PlanfixIntegration\PlanfixIntegrationEntity;
use danog\MadelineProto\API;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlanfixChatController extends Controller
{
    public function handle(Request $request)
    {
        Log::channel('planfix-messages')->info('Planfix webhook received:', $request->all());

        $data = $request->all();


        if (!isset($data['chatId']) || (empty($data['message']) && empty($data['attachments']))) {
            Log::channel('planfix-messages')->error('Invalid Planfix webhook data: Missing required fields.');
            return response()->json(['success' => false, 'error' => 'Missing required fields.'], 400);
        }

        $chatId = $data['chatId'];
        $message = $data['message'] ?? ''; // Текст сообщения (может быть null)
        $attachments = $data['attachments'] ?? null; // Вложения
        $token = $data['token'];

        try {
//            $planfixIntegration = DB::table('planfix_integrations')
//                ->where('token', $token)
//                ->first();

            $planfixIntegration = PlanfixIntegrationEntity::findByToken($token);




            if (!$planfixIntegration) {
                Log::channel('planfix-messages')->error("No Telegram account found for token: {$token}");
                return response()->json(['success' => false, 'error' => 'Invalid token.'], 400);
            }
//
//            $telegramAccount = DB::table('telegram_accounts')
//                ->where('id', $planfixIntegration->telegram_account_id)
//                ->first();

            $telegramAccount = PlanfixIntegrationEntity::getTelegramFromId($planfixIntegration->getId());


            if (!$telegramAccount) {
                Log::channel('planfix-messages')->error("No Telegram account found for ID: {$planfixIntegration->getTelegramAccountId()}");
                return response()->json(['success' => false, 'error' => 'Telegram account not found.'], 400);
            }

//            $session = storage_path('telegram_sessions/79171275883.madeline');
            if ($telegramAccount->status === 'Пауза') {
                Log::channel('planfix-messages')->info("Telegram session is on pause for account ID: {$telegramAccount->id}");
                return response()->json(['success' => false, 'error' => 'Telegram session is on pause.'], 400);
            }


            $madelineProto =  new API($telegramAccount->session_path);

            Log::info('СЕССИЯ ВОТ ТАКАЯ ' );
            Log::info("PEER ВОТ ТАКОЙ" . $chatId);
            $madelineProto->getPwrChat($chatId);

            if ($attachments) {

                sleep(5);

                $fileUrl = $attachments['url'];
                $fileName = $attachments['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                if (in_array($fileExtension, ['png', 'jpg', 'jpeg'])){

                  $resultPNG = $madelineProto->messages->sendMedia([
                        'peer' => $chatId,
                        'media' => [
                            '_' => 'inputMediaUploadedPhoto', // Для изображений
                            'file' => $fileUrl,
                        ],
                        'message' => $message . "\u{200B}", // Скрытый символ
                        'entities' => null,
                    ]);
                    Log::channel('planfix-messages')->info("КАРТИНКА ИЗ PLANFIX to Telegram chat", [$resultPNG['updates'][1]['message']['media']['photo']['id']]);

                    $idMessageMedia = $resultPNG['updates'][1]['message']['media']['photo']['id'];

                    Log::info("ID MANAGER " . $telegramAccount->telegram_id);
                    Log::info("ПРИВЕТ");


                    /** @var TelegramAccount $telegramAccount */
                    DB::table('id_message_to_tg_telegram')->insert([
                        'message_id' => $idMessageMedia,
                        'manager_id' => $telegramAccount->telegram_id
                    ]);



                    $madelineProto->messages->readHistory([
                        'peer' => $chatId
                    ]);



                }elseif (in_array($fileExtension, ['mp4', 'mkv', 'mov', 'avi'])){
                    $resultMP4 = $madelineProto->messages->sendMedia([
                        'peer' => $chatId,
                        'media' => [
                            '_' => 'inputMediaUploadedDocument',
                            'file' => $fileUrl,
                            'attributes' => [
                                [
                                    '_' => 'documentAttributeVideo',
                                ]
                            ]
                        ],
                        'message' => $message . "\u{200B}", // Скрытый символ
                        'entities' => [
                            [
                                '_' => 'messageEntityTextUrl', // Тип сущности
                                'offset' => 0,                // Позиция в тексте
                                'length' => 1,                // Длина символа
                                'url' => 'planfix://internal' // Метка
                            ]
                        ],
                    ]);



                    Log::channel('planfix-messages')->info("ВИДЕО ИЗ PLANFIX to Telegram chat", [$resultMP4['updates'][1]['message']['media']['document']['id']]);

                    $idMessageMedia = $resultMP4['updates'][1]['message']['media']['document']['id'];

                    Log::info("ID MANAGER " . $telegramAccount->telegram_id);
                    Log::info("ПРИВЕТ");

                    /** @var TelegramAccount $telegramAccount */
                    DB::table('id_message_to_tg_telegram')->insert([
                        'message_id' => $idMessageMedia,
                        'manager_id' => $telegramAccount->telegram_id
                    ]);


                    $madelineProto->messages->readHistory([
                        'peer' => $chatId
                    ]);

                    Log::channel('planfix-messages')->info("Attachment sent to Telegram chat {$chatId}: {$fileName}");
                }

                elseif (in_array($fileExtension, ['ogg', 'ogg.ogx', 'ogx'])){
                    $madelineProto->messages->readHistory([
                        'peer' => $chatId
                    ]);

                    function estimateDurationBySize($fileUrl)
                    {
                        // Получаем размер файла (в байтах)
                        $headers = get_headers($fileUrl, 1);
                        $fileSize = $headers['Content-Length'] ?? 0;

                        // Преобразуем в килобайты
                        $fileSizeKB = $fileSize / 1024;

                        // Предполагаем, что 40 КБ = 10 секунд
                        return ($fileSizeKB / 40) * 10; // Длительность в секундах
                    }

                    $duration = estimateDurationBySize($fileUrl);

                    Log::channel('planfix-messages')->info('ДЛИТЕЛЬНОСТЬ ГОЛОСОВОЙ' . $duration);

                    $typingDuration = (int)$duration;
                    $interval = 5;
                    $startTime = time();



                    while (time() - $startTime < $typingDuration) {
                        $madelineProto->messages->setTyping([
                            'peer' => $chatId,
                            'action' => [
                                '_' => 'sendMessageRecordAudioAction',
                            ]
                        ]);

                    }

                    $resultOgg = $madelineProto->messages->sendMedia([
                        'peer' => $chatId,
                        'media' => [
                            '_' => 'inputMediaUploadedDocument',
                            'file' => $fileUrl,
                            'attributes' => [
                                [
                                    '_' => 'documentAttributeAudio',
                                    'voice' => true
                                ]
                            ]
                        ],
                        'message' => $message . "\u{200B}", // Скрытый символ
                        'entities' => [
                            [
                                '_' => 'messageEntityTextUrl', // Тип сущности
                                'offset' => 0,                // Позиция в тексте
                                'length' => 1,                // Длина символа
                                'url' => 'planfix://internal' // Метка
                            ]
                        ],
                    ]);

                    Log::channel('planfix-messages')->info("ГОЛОСОВОЕ ИЗ PLANFIX to Telegram chat", [$resultOgg['updates'][1]['message']['media']['document']['id']]);

                    $idMessageMedia = $resultOgg['updates'][1]['message']['media']['document']['id'];

                    Log::info("ID MANAGER " . $telegramAccount->telegram_id);
                    Log::info("ПРИВЕТ");

                    /** @var TelegramAccount $telegramAccount */
                    DB::table('id_message_to_tg_telegram')->insert([
                        'message_id' => $idMessageMedia,
                        'manager_id' => $telegramAccount->telegram_id
                    ]);




                    Log::channel('planfix-messages')->info("Attachment sent to Telegram chat {$chatId}: {$fileName}");
                }

            } elseif ($message) {


                $madelineProto->messages->readHistory([
                    'peer' => $chatId
                ]);

                $messageLength = mb_strlen($message); // Определяем длину сообщения
                $typingDuration = 0;

                if ($messageLength < 20) {
                    $typingDuration = 3;
                } elseif ($messageLength < 100) {
                    $typingDuration = 8;
                } elseif ($messageLength < 300) {
                    $typingDuration = 15;
                } elseif ($messageLength < 500) {
                    $typingDuration = 20;
                } else {
                    $typingDuration = 30;
                }



                $interval = 5; // Интервал между повторными вызовами (можно менять для оптимизации)
                $startTime = time();

                while (time() - $startTime < $typingDuration) {
                    $madelineProto->messages->setTyping([
                        'peer' => $chatId,
                        'action' => [
                            '_' => 'sendMessageTypingAction',
                        ],
                    ]);

                }


               $resultMessage = $madelineProto->messages->sendMessage([
                    'peer' => $chatId,
                    'message' => $message,
                    'entities' => [
                        [
                            '_' => 'messageEntityTextUrl', // Скрытая ссылка
                            'offset' => strlen($message), // Ставим в конец текста
                            'length' => 1,                // Один символ
                            'url' => 'planfix://internal' // Невидимая метка
                        ]
                    ],
                ]);


                Log::channel('planfix-messages')->info("ТЕКСТ ИЗ PLANFIX to Telegram chat" . $resultMessage['id']);

                $idMessageMedia = $resultMessage['id'];
                /** @var TelegramAccount $telegramAccount */
                Log::channel('planfix-messages')->info("ID MANAGER " . $telegramAccount->telegram_id);
                Log::channel('planfix-messages')->info("ПРИВЕТ");


                DB::table('id_message_to_tg_telegram')->insert([
                    'message_id' => $idMessageMedia,
                    'manager_id' => $telegramAccount->telegram_id
                ]);





                Log::channel('planfix-messages')->info("Text message sent to Telegram chat {$chatId}: {$message}");
            }

            return response()->json(['success' => true]);

        }catch (\Throwable $e){
            Log::channel('planfix-messages')->error('Failed to send message to Telegram: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to send message to Telegram'], 500);
        }



    }
}
