<?php

namespace Splitstack\Translucid\Concerns;

use Illuminate\Database\Eloquent\Model;
use Laravel\Pennant\Feature;
use Splitstack\Translucid\Events\TranslucidCreated;
use Splitstack\Translucid\Events\TranslucidDeleted;
use Splitstack\Translucid\Events\TranslucidUpdated;
use Splitstack\Translucid\Features\TranslucidFromApp;
use Splitstack\Translucid\Features\TranslucidFromDB;
use Splitstack\Translucid\Translucid;

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

    private static function translucidScope(): ?string
    {
        if (! config('translucid.tenant_driver')) {
            return null;
        }

        return Translucid::resolveChannel();
    }
}
