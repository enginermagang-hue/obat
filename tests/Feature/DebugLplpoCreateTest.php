<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

class DebugLplpoCreateTest extends BaseTestCase
{
    public function test_create_page_loads()
    {
        $user = User::where('email', 'puskesmas@mail.com')->first();
        if (! $user) {
            echo "User not found\n";

            return;
        }
        echo 'User: '.$user->email.' ('.$user->id.")\n";

        $response = $this->actingAs($user)->get('/admin/lplpo/create');
        echo 'Status: '.$response->getStatusCode()."\n";
        echo 'Body length: '.strlen($response->getContent())."\n";

        if ($response->getStatusCode() === 500) {
            // Try to find the exception
            $exception = $response->exception;
            if ($exception) {
                echo 'Exception: '.get_class($exception).': '.$exception->getMessage()."\n";
                echo $exception->getTraceAsString()."\n";
            } else {
                echo "No exception object in response\n";
            }
        }
    }
}
