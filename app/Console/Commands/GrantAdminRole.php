<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GrantAdminRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Получаем всех пользователей
        $users = DB::table('users')
            ->leftJoin('roles', 'users.id', '=', 'roles.user_id')
            ->select('users.id', 'users.name', 'roles.title as role')
            ->get();

        // Выводим список пользователей
        $this->info("Список пользователей:");
        foreach ($users as $user) {
            $this->line("ID: {$user->id}, Имя: {$user->name}, Роль: " . ($user->role ?? 'нет роли'));
        }

        // Запрашиваем ID пользователя
        $userId = $this->ask("Введите ID пользователя, которому хотите назначить роль admin");

        // Проверяем, существует ли пользователь
        $user = DB::table('users')->where('id', $userId)->first();

        if (!$user) {
            $this->error("Пользователь с ID {$userId} не найден.");
            return;
        }

        // Обновляем роль
        DB::table('roles')
            ->updateOrInsert(
                ['user_id' => $userId], // Условие
                ['title' => 'admin']    // Новые данные
            );

        $this->info("Пользователю с ID {$userId} назначена роль admin.");
    }
}
