<?php

namespace Splitstack\Translucid\Providers;

use Illuminate\Support\ServiceProvider;
use Splitstack\Translucid\Console\Commands\TranslucidListen;
use Splitstack\Translucid\Contracts\TenantDriver;
use Splitstack\Translucid\Translucid;

class TranslucidServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Translucid::class);
        $this->app->alias(Translucid::class, 'translucid');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/translucid.php' => config_path('translucid.php'),
        ], 'translucid-config');

        $this->mergeConfigFrom(__DIR__.'/../config/translucid.php', 'translucid');

        $this->bootTenantDriver();

        if ($this->app->runningInConsole()) {
            $this->commands([
                TranslucidListen::class,
            ]);
        }
    }

    protected function bootTenantDriver(): void
    {
        $driverClass = config('translucid.tenant_driver');

        if (! $driverClass || ! class_exists($driverClass)) {
            return;
        }

        $driver = $this->app->make($driverClass);

        if (! $driver instanceof TenantDriver) {
            return;
        }

        Translucid::resolveChannelUsing(fn () => $driver->resolveChannel());
        Translucid::resolveListenConnectionsUsing(fn () => $driver->resolveListenConnections());
    }
}
