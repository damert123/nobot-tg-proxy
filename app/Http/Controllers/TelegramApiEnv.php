<?php

namespace App\Http\Controllers;

use App\Console\Commands\RestartMadelineProto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class TelegramApiEnv extends Controller
{
    public function create()
    {
        return view('telegram.api');
    }

    public function store(Request $request)
    {
        $request->validate([
            'api_id' => 'required|numeric',
            'api_hash' => 'required|string'
        ]);

        $this->updateEnv([
            'TELEGRAM_API_ID' => $request->input('api_id'),
            'TELEGRAM_API_HASH' => $request->input('api_hash'),
        ]);

        try {
            Artisan::call('telegram:restart');
        }catch (\Exception $e){
            return redirect()->route('telegram.api')->withErrors('Ошибка: ' . $e->getMessage());
        }

        return redirect()->route('telegram.api.add')->with('success', 'Telegram API данные успешно сохранены!');
    }

    public function restart()
    {
        Artisan::call('telegram:restart');

        return redirect()->route('dashboard')->with('success', 'Telegram сессии перезапущены успешно!');
    }

    private function updateEnv(array $data)
    {
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            File::put($envPath, '');
        }

        $envContent = File::get($envPath);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*/m";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, "{$key}={$value}", $envContent);
            } else {
                $envContent .= PHP_EOL . "{$key}={$value}";
            }
        }

        File::put($envPath, $envContent);



    }


}
