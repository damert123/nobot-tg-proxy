<?php

namespace App\Http\Controllers;

use App\Http\Requests\Telegram\StoreRequest;
use App\Models\TelegramAccount;
use danog\MadelineProto\API;
use Illuminate\Http\Request;

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

        $telegramAccount = TelegramAccount::create([
            'phone' => $data['phone'],
            'title' => $data['title'],
            'user_id' => auth()->id(), // Если аккаунт привязан к пользователю
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
        } catch (\Exception $e) {
            return redirect()->route('dashboard')->withErrors('Ошибка отправки кода: ' . $e->getMessage());
        }

    }

    public function verifyCode(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|exists:telegram_accounts,phone',
            'code' => 'required|string|min:5|max:5',
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

            $madelineProto->completePhoneLogin($validated['code']);

            $madelineProto->start();

            $self = $madelineProto->getSelf();

            if (!isset($self['id'])) {
                throw new \RuntimeException('Не удалось получить ID Telegram-аккаунта.');
            }

            $telegramAccount = TelegramAccount::updateOrCreate(
                ['phone' => $validated['phone']], // Обновляем, если номер телефона уже существует
                [
                    'telegram_id' => $self['id'],
                    'session_path' => $sessionFile,
                    'user_id' => auth()->id(),
                ]
            );

            return redirect()->route('dashboard')->with('success', 'Аккаунт успешно добавлен!');


        }catch (\Exception $e){
            return back()->withErrors(['code' => 'Ошибка авторизации: ' . $e->getMessage()]);


        }
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

            $account->delete();

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
//    protected function deleteSessionFolder($dir)
//    {
//        if (is_dir($dir)){
//            $files = array_diff(scandir($dir), ['.', '..']);
//
//            foreach ($files as $file){
//                $filesPath = $dir . DIRECTORY_SEPARATOR . $file;
//
//                if (is_dir($filesPath)){
//                    $this->deleteSessionFolder($filesPath);
//                }else {
//                    unlink($filesPath);
//                }
//
//            }
//
//            rmdir($dir);
//        }
//
//    }


}
