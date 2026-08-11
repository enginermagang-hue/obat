<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class UpdateLastActiveOnLogin
{
    public function handle(Login $event): void
    {
        if ($event->user) {
            $event->user->updateQuietly(['last_active_at' => now()]);
        }
    }
}
