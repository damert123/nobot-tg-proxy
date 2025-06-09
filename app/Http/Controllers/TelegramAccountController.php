<?php

namespace App\Http\Controllers;

use App\Http\Requests\Telegram\StoreRequest;
use App\Models\PlanfixIntegration;
use App\Models\TelegramAccount;
use danog\MadelineProto\API;
use danog\MadelineProto\RPCError\SessionPasswordNeededError;
use danog\MadelineProto\Settings\AppInfo;
use danog\MadelineProto\Tools;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class TelegramAccountController extends Controller
{
    public function index()
    {
        $accounts = TelegramAccount::all();

        return view('telegram.index', compact('accounts'));
    }



    public function create()
    {
        return view('telegram.add');
    }

    public function store(StoreRequest $request)
    {

        $data = $request->validated();

        $sessionFile = storage_path("telegram_sessions/{$data['phone']}.madeline");


        $telegramAccount = TelegramAccount::create([
            'phone' => $data['phone'],
            'title' => $data['title'],
            'status' => 'Ожидает код',
            'session_path' => $sessionFile,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('telegram.code', ['phone' => $telegramAccount->phone])
            ->with('success', 'Введите код подтверждения.');

    }


    public function showCodeForm(Request $request)
    {
        $phone = $request->query('phone');


        if (!$phone) {
            return redirect()->route('dashboard')->withErrors('Номер телефона отсутствует!');
        }

        try {
            // Инициализация настроек MadelineProto
            $settings = (new \danog\MadelineProto\Settings\AppInfo)
                ->setApiId(env('TELEGRAM_API_ID'))
                ->setApiHash(env('TELEGRAM_API_HASH'));

            // Путь к файлу сессии
            $sessionFile = storage_path("telegram_sessions/{$phone}.madeline");

            // Создание API-инстанса и выполнение phoneLogin
            $madelineProto = new \danog\MadelineProto\API($sessionFile, $settings);
            $madelineProto->phoneLogin($phone);






            // Передаём номер телефона в представление
            return view('telegram.code', compact('phone'));
        }


        catch (\Exception $e) {
            return redirect()->route('telegram.add')->withErrors('Ошибка отправки кода: ' . $e->getMessage());
        }

    }

    public function verifyCode(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|exists:telegram_accounts,phone',
            'code' => 'required|string|min:5|max:5',
            'password' => 'nullable|string',
        ], [
            'phone.exists' => 'Аккаунт с таким номером телефона не найден.',
            'code.required' => 'Введите код подтверждения.',
            'code.max' => 'Код должен содержать максимум 5 символов.',
        ]);

        try {
            $settings = (new \danog\MadelineProto\Settings\AppInfo)
                ->setApiId(env('TELEGRAM_API_ID'))
                ->setApiHash(env('TELEGRAM_API_HASH'));

            $sessionFile = storage_path("telegram_sessions/{$validated['phone']}.madeline");

            $madelineProto = new API($sessionFile, $settings);

           $test2fa = $madelineProto->completePhoneLogin($validated['code']);

//            if ($test2fa['_'] === 'account.password') {
//                redirect()->route('telegram.twofactor');
////                $authorization = $madelineProto->complete2falogin(Tools::readLine('Please enter your password (hint '.$test2fa['hint'].'): '));
//            }


           if ($validated['password']){
               $madelineProto->complete2faLogin($validated['password']);
           }


            $madelineProto->start();

            $self = $madelineProto->getSelf();

            if (!isset($self['id'])) {
                throw new \RuntimeException('Не удалось получить ID Telegram-аккаунта.');
            }

            $telegramAccount = TelegramAccount::updateOrCreate(
                ['phone' => $validated['phone']], // Обновляем, если номер телефона уже существует
                [
                    'telegram_id' => $self['id'],
                    'status' => 'Пауза',
                ]
            );

            return redirect()->route('dashboard')->with('success', 'Аккаунт успешно добавлен!');


        }
        catch (\Exception $e){
            return back()->withErrors(['code' => 'Ошибка авторизации: ' . $e->getMessage()]);

        }



    }

    public function resendCode(Request $request, string $phone)
    {
//        $phone = $request->input('phone');
        $phoneCodeHash = '40dcf1ed51d4b29095';

        if (!$phone){
            return redirect()->route('telegram.index')->withErrors('Номер телефона отсутствует!');
        }

        try {
            $sessionFile = storage_path("telegram_sessions/{$phone}.madeline");

            $settings = (new \danog\MadelineProto\Settings\AppInfo)
                ->setApiId(env('TELEGRAM_API_ID'))
                ->setApiHash(env('TELEGRAM_API_HASH'));

            $madelineProto = new API($sessionFile, $settings);

            $madelineProto->auth->resendCode(['phone_number' => $phone, 'phone_code_hash' => $phoneCodeHash]);

            return redirect()->route('telegram.code', ['phone' => $phone])
                ->with('success', 'Код был повторно отправлен, Пожалуйста, введтие его!');

        }catch (\Exception $e){
            return  redirect()->route('telegram.index')->withErrors('Ошибки при повторной отправке кода' . $e->getMessage());
        }
    }


    public function showTwoFactorCode()
    {

        return view('telegram.twofactor');
    }


    public function verifyTwoFactorCode()
    {

//        $madelineProto->complete2faLogin('Rotika123');

    }

    public function destroy($id)
    {
        try {
            $account = TelegramAccount::findOrFail($id);
            $sessionPath = $account->session_path;

            if (file_exists($sessionPath) && is_dir($sessionPath)) {
                // Удаляем папку с сессией
                $this->deleteSessionFolder($sessionPath);
            }

            PlanfixIntegration::where('telegram_account_id', $account->id)->delete();

            $account->delete();

            Artisan::call('telegram:restart-supervisor');

            return redirect()->route('telegram.index')->with('success', 'Аккаунт успешно удален!');

        }catch (\Exception $e){
            return back()->withErrors(['error' => 'Ошибка при удалении аккаунта: ' . $e->getMessage()]);
        }


    }


    /**
     * Удаляет папку с сессией
     *
     * @param string $dir
     * @return void
     */
    protected function deleteSessionFolder($dir)
    {
        if (is_dir($dir)){
            $files = array_diff(scandir($dir), ['.', '..']);

            foreach ($files as $file){
                $filesPath = $dir . DIRECTORY_SEPARATOR . $file;

                if (is_dir($filesPath)){
                    $this->deleteSessionFolder($filesPath);
                }else {
                    unlink($filesPath);
                }

            }

            rmdir($dir);
        }

    }

    public function start(int $accountId)
    {
        // Получаем аккаунт по ID
        $account = TelegramAccount::findOrFail($accountId);

        // Проверяем, что сессия не активна
        if ($account->status === 'Активен') {
            return redirect()->route('telegram.index')->with('error', 'Сессия уже активна.');
        }

        // Статус сессии меняем на "Активен"
        $account->status = 'Активен';
        $account->save();

        // Запуск сессии с использованием MadelineProto

        Artisan::call('telegram:restart-supervisor');

        return redirect()->route('telegram.index')->with('success', 'Сессия была успешно запущена.');
    }

    public function stop(int $accountId)
    {
        // Получаем аккаунт по ID
        $account = TelegramAccount::findOrFail($accountId);

        // Проверяем, что сессия не активна
        if ($account->status === 'Пауза') {
            return redirect()->route('telegram.index')->with('error', 'Сессия уже на паузе.');
        }

        // Статус сессии меняем на "Активен"
        $account->status = 'Пауза';
        $account->save();

        Artisan::call('telegram:restart-supervisor');

        return redirect()->route('telegram.index')->with('success', 'Сессия была поставлена на паузу.');
    }




}
