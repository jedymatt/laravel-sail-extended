<?php

namespace Jedymatt\LaravelSailExtended;

use Illuminate\Support\ServiceProvider;

class SailExtendedServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\AddServicesCommand::class,
            ]);
        }
    }
}
