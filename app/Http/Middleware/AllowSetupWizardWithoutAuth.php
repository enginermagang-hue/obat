<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AllowSetupWizardWithoutAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('filament.admin.pages.setup-wizard')) {
            return $next($request);
        }

        if (! auth()->check()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
