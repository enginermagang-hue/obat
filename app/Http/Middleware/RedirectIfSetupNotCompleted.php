<?php

namespace App\Http\Middleware;

use App\Models\SetupConfiguration;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfSetupNotCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        // Jangan redirect di halaman setup wizard itu sendiri
        if ($request->routeIs('setup-wizard.show', 'setup-wizard.store', 'filament.admin.pages.setup-wizard')) {
            return $next($request);
        }

        // Skip middleware untuk login, logout, dan auth routes
        if ($request->routeIs('login', 'login.post', 'logout')) {
            return $next($request);
        }

        // Cek apakah setup sudah selesai
        if (! SetupConfiguration::isSetupCompleted()) {
            return redirect()->route('setup-wizard.show');
        }

        return $next($request);
    }
}
