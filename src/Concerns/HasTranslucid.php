<?php

namespace Splitstack\Translucid\Concerns;

use App\Models\Landlord\Tenant;
use Illuminate\Database\Eloquent\Model;
use Laravel\Pennant\Feature;
use Splitstack\Translucid\Contracts\TenantDriver;
use Splitstack\Translucid\Events\TranslucidCreated;
use Splitstack\Translucid\Events\TranslucidDeleted;
use Splitstack\Translucid\Events\TranslucidUpdated;
use Splitstack\Translucid\Features\TranslucidFromApp;
use Splitstack\Translucid\Features\TranslucidFromDB;

trait HasTranslucid
{
    protected static function bootHasTranslucid(): void
    {
        self::created(function (Model $model) {
            if (! Feature::for(static::translucidScope())->someAreActive([TranslucidFromApp::class, TranslucidFromDB::class])) {
                return;
            }
            event(new TranslucidCreated($model));
        });

        self::updated(function (Model $model) {
            if (! Feature::for(static::translucidScope())->someAreActive([TranslucidFromApp::class, TranslucidFromDB::class])) {
                return;
            }
            event(new TranslucidUpdated($model));
        });

        self::deleted(function (Model $model) {
            if (! Feature::for(static::translucidScope())->someAreActive([TranslucidFromApp::class, TranslucidFromDB::class])) {
                return;
            }
            event(new TranslucidDeleted($model));
        });
    }

    private static function translucidScope(): mixed
    {
        $driverClass = config('translucid.tenant_driver');
        if (! $driverClass) {
            return null;
        }

        /** @var TenantDriver $driver */
        $driver = app($driverClass);

        return $driver->resolveFeatureScope(Tenant::current());
    }
}
