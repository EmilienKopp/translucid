<?php

namespace Splitstack\Translucid\Facades;

use Illuminate\Support\Facades\Facade;

class Translucid extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'translucid';
    }
}
