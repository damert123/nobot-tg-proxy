<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {

        $users = DB::table('users')
            ->leftJoin('roles', 'users.id', '=', 'roles.user_id')
            ->select('users.*', 'roles.title as role')
            ->get();


        return view('users.index', compact('users'));

    }

    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|string|in:admin,user',
        ]);

        // Обновляем роль в таблице roles
        DB::table('roles')
            ->updateOrInsert(
                ['user_id' => $id], // Поиск по user_id
                ['title' => $request->input('role'), 'updated_at' => now()] // Обновляем или вставляем роль
            );

        return redirect()->back()->with('success', 'Роль пользователя успешно обновлена!');



    }


}
