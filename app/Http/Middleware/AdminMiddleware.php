<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (!auth()->check()){
            return response([
                'message' => 'Unauthorized'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $userId = auth()->id();

        $hasAdminRole = DB::table('roles')
            ->where('user_id', $userId)
            ->where('title', 'admin')
            ->exists();

        if (!$hasAdminRole){
            return response([
                'message' => 'Access denied: not admin'
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);

    }
}
