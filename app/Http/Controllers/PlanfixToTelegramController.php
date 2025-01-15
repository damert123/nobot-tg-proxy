<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessTelegramMessageJob;
use App\Services\PlanfixService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PlanfixToTelegramController extends Controller
{
    protected PlanfixService $planfixService;

    public function __construct(PlanfixService $planfixService)
    {
        $this->planfixService = $planfixService;
    }

    public function handle(Request $request)
    {

        try {
            Log::channel('planfix-messages')->info('Planfix webhook received:', $request->all());

            $data = $request->all();

            $this->planfixService->validateWebhookData($data);

            ProcessTelegramMessageJob::dispatch($data)->onQueue('default');


//            response()->json(['status' => 'received'], 200)->send();
//
//            fastcgi_finish_request();
//
//            $token = $data['token'];
//            $telegramAccount = $this->planfixService->getIntegrationAndAccount($token);
//
//            $madelineProto = $this->planfixService->initializeModelineProto($telegramAccount->session_path);
//
//            $chatId = $data['chatId'];
//            $message = $data['message'] ?? null;
//
//            if ($message){
//                $this->planfixService->sendMessage($madelineProto, $chatId, $message);
//            }
//
//            if (!empty($data['attachments'])){
//                $this->planfixService->sendAttachment($madelineProto, $chatId, $data['attachments'], $message );
//            }

            return response()->json(['status' => 'received'], 200);
        }catch (\Exception $e){
            Log::channel('planfix-messages')->error("Ошибка обработки вебхука: {$e->getMessage()}");
            return response()->json(['error' => $e->getMessage()], 400);
        }






    }
}
