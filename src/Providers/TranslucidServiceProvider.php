<?php

namespace Splitstack\Translucid\Providers;

use Illuminate\Support\ServiceProvider;
use Splitstack\Translucid\Console\Commands\TranslucidListen;
use Splitstack\Translucid\Translucid;

class TranslucidServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Translucid::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/translucid.php' => config_path('translucid.php'),
        ], 'translucid-config');

        $this->mergeConfigFrom(__DIR__.'/../config/translucid.php', 'translucid');

        if ($this->app->runningInConsole()) {
            $this->commands([
                TranslucidListen::class,
            ]);
        }
    }
}
