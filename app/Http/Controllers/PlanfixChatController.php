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
        Log::info('Planfix webhook received:', $request->all());

        $data = $request->all();


        if (!isset($data['message'], $data['chatId'])) {
            Log::error('Invalid Planfix webhook data: Missing required fields.');
            return response()->json(['success' => false, 'error' => 'Missing required fields.'], 400);
        }

        $chatId = $data['chatId']; // ID чата в Telegram
        $message = $data['message']; // Текст сообщения
        $token = $data['token'];

        try {

            $planfixIntegration = DB::table('planfix_integrations')
                ->where('token', $token)
                ->first();

            if (!$planfixIntegration) {
                Log::error("No Telegram account found for token: {$token}");
                return response()->json(['success' => false, 'error' => 'Invalid token.'], 400);
            }

            $telegramAccount = DB::table('telegram_accounts')
                ->where('id', $planfixIntegration->telegram_account_id)
                ->first();

            if (!$telegramAccount) {
                Log::error("No Telegram account found for ID: {$planfixIntegration->telegram_account_id}");
                return response()->json(['success' => false, 'error' => 'Telegram account not found.'], 400);
            }

//            $session = storage_path('telegram_sessions/79171275883.madeline');

            $madelineProto =  new API($telegramAccount->session_path);

            Log::info('СЕССИЯ ВОТ ТАКАЯ ' );
            Log::info("PEER ВОТ ТАКОЙ" . $chatId);
            // Принудительное разрешение чата
            $madelineProto->getPwrChat($chatId);
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

            Log::info("Message sent to Telegram chat {$chatId}: {$message}");

            return response()->json(['success' => true]);

        }catch (\Throwable $e){
            Log::error('Failed to send message to Telegram: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to send message to Telegram'], 500);
        }



    }
}
