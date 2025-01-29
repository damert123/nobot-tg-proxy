<?php

namespace App\Modules\ApiNoBot;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiNobotService
{
    private const BASE_URL = 'https://crm.nobot.ru/rest';
    private const TOKEN = '676cf835d18cb108d6d76ee789c52d5a';

    private function request(string $method, string $endpoint, array $query = []): ?array
    {
        $url = self::BASE_URL . $endpoint;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer' . self::TOKEN,
            'Accept' => 'application/json',
        ])->$method($url, $query);

        if ($response->failed()){
            Log::channel('top-up-messages')->error("CRM API ERROR: " . $response->body());
            return null;
        }

        return $response->json();

    }

    public function getTask(int $taskId): ?array
    {
        return $this->request('get', "/task/{$taskId}", ['fields' => 'id,assigner']);
    }

    public function getContact(string $contactId): ?array
    {
        return $this->request('get', "/contact/{$contactId}", ['fields' => 'telegram']);
    }

    public function extractUsernameFromLink(string $link): ?string
    {
        // Убираем https://, http://, t.me/ и @
        $link = preg_replace('/^(https?:\/\/)?(t\.me\/|@)/', '', $link);

        return $link ? '@' . $link : null;
    }






}
