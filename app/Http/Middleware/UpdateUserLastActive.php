<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserLastActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        /** @var User|null $user */
        $user = $request->user();

        if ($user) {
            $user->setAttribute('last_active_at', now())->saveQuietly();
        }

        return $response;
    }
}
