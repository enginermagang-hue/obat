<?php

namespace Stokobat\Boxicons;

use BladeUI\Icons\Factory;
use Illuminate\Support\ServiceProvider;

class BoxiconsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->callAfterResolving(Factory::class, function (Factory $factory): void {
            $factory->add('default', [
                'prefix' => 'bx',
                'path' => __DIR__.'/../resources/svg/regular',
            ]);

            $factory->add('boxicons-solid', [
                'prefix' => 'bxs',
                'path' => __DIR__.'/../resources/svg/solid',
            ]);

            $factory->add('boxicons-logos', [
                'prefix' => 'bxl',
                'path' => __DIR__.'/../resources/svg/logos',
            ]);
        });
    }
}
