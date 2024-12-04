<?php

namespace App\Http\Controllers;

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

            $planfixIntegration = DB::table('planfix_integrations')
                ->where('token', $token)
                ->first();

            if (!$planfixIntegration) {
                Log::channel('planfix-messages')->error("No Telegram account found for token: {$token}");
                return response()->json(['success' => false, 'error' => 'Invalid token.'], 400);
            }

            $telegramAccount = DB::table('telegram_accounts')
                ->where('id', $planfixIntegration->telegram_account_id)
                ->first();

            if (!$telegramAccount) {
                Log::channel('planfix-messages')->error("No Telegram account found for ID: {$planfixIntegration->telegram_account_id}");
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

                $fileUrl = $attachments['url'];
                $fileName = $attachments['name'];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));



                if (in_array($fileExtension, ['png', 'jpg', 'jpeg'])){
                    $madelineProto->messages->sendMedia([
                        'peer' => $chatId,
                        'media' => [
                            '_' => 'inputMediaUploadedPhoto', // Для изображений
                            'file' => $fileUrl,
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
                    Log::channel('planfix-messages')->info("Attachment sent to Telegram chat {$chatId}: {$fileName}");
                    $madelineProto->messages->readHistory([
                        'peer' => $chatId
                    ]);
                }elseif (in_array($fileExtension, ['mp4', 'mkv', 'mov', 'avi'])){
                    $madelineProto->messages->sendMedia([
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
                    $madelineProto->messages->readHistory([
                        'peer' => $chatId
                    ]);

                    Log::channel('planfix-messages')->info("Attachment sent to Telegram chat {$chatId}: {$fileName}");
                }

                elseif (in_array($fileExtension, ['ogg'])){
                    $madelineProto->messages->sendMedia([
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
                    $madelineProto->messages->readHistory([
                        'peer' => $chatId
                    ]);

                    Log::channel('planfix-messages')->info("Attachment sent to Telegram chat {$chatId}: {$fileName}");
                }

            } elseif ($message) {
                // Отправка текстового сообщения, если вложений нет
                $madelineProto->messages->sendMessage([
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
                $madelineProto->messages->readHistory([
                    'peer' => $chatId
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
