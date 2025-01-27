<?php

namespace App\Http\Controllers;

use App\Http\Requests\Planfix\StoreRequest;
use App\Models\PlanfixIntegration;
use App\Models\TelegramAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlanfixController extends Controller
{
    public function index()
    {
//        $integrations = PlanfixIntegration::all();
        $integrations = DB::table('planfix_integrations')
            ->leftJoin('telegram_accounts', 'planfix_integrations.telegram_account_id', '=', 'telegram_accounts.id')
            ->select(
                'planfix_integrations.*',
                'telegram_accounts.title as telegram_account_title',
                'telegram_accounts.phone as telegram_account_phone'
            )
            ->get();

        foreach ($integrations as $integration) {
            $integration->telegram_account_info = $integration->telegram_account_title
                ? "{$integration->telegram_account_title} (+{$integration->telegram_account_phone})"
                : 'Не привязан';
        }

        return view('planfix.index', compact('integrations'));
    }

    public function create()
    {
        $telegramAccounts = DB::table('telegram_accounts')
            ->leftJoin('planfix_integrations', 'telegram_accounts.id', '=', 'planfix_integrations.telegram_account_id')
            ->whereNull('planfix_integrations.telegram_account_id')
            ->select('telegram_accounts.id', 'telegram_accounts.phone', 'telegram_accounts.title')
            ->get();

        return view('planfix.add', compact('telegramAccounts'));
    }

    public function store(StoreRequest $request)
    {
        $data = $request->validated();

        $generatedToken = Str::random(32);

        $planfixIntegration = PlanfixIntegration::create([
            'provider_id' => $data['provider_id'],
            'planfix_token' => $data['token'],
            'name' => $data['name'],
            'token' => $generatedToken,
            'telegram_account_id' => $data['telegram_account_id'],
        ]);

        return redirect()->route('planfix.index')->with('success', "Интеграция добавлена! ВСТАВЬТЕ ТОКЕН: {$generatedToken} ");

    }

    public function destroy($id)
    {
        try {
            $account = PlanfixIntegration::findOrFail($id);

            $account->delete();

            return redirect()->route('planfix.index')->with('success', 'Чат Planfix успешно удален!');

        }catch (\Exception $e){
            return back()->withErrors(['error' => 'Ошибка при удалении аккаунта: ' . $e->getMessage()]);
        }
    }
}
