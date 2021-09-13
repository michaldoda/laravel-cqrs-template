<?php

namespace App\Infrastructure\Laravel\Providers;

use App\Infrastructure\CommandBus\CommandBus;
use App\Infrastructure\CommandBus\CommandBusInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(CommandBusInterface::class, CommandBus::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
