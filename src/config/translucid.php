<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcast Channel
    |--------------------------------------------------------------------------
    | The base channel name used for Translucid broadcasts. In multi-tenant
    | mode this becomes the prefix (e.g. "translucid.acme").
    */
    'default_channel' => 'translucid',

    /*
    |--------------------------------------------------------------------------
    | Tenant Driver
    |--------------------------------------------------------------------------
    | Set a driver class to enable per-tenant channel scoping and per-tenant
    | PostgreSQL LISTEN connections. Null means single-tenant (no scoping).
    |
    | Built-in driver for spatie/laravel-multitenancy:
    | \Splitstack\Translucid\Tenancy\SpatieMultitenancyDriver::class
    */
    'tenant_driver' => null,

    /*
    |--------------------------------------------------------------------------
    | Tenant Channel Attribute
    |--------------------------------------------------------------------------
    | The attribute on the Tenant model appended to the channel name.
    | Produces channels like "translucid.{tenant.space}".
    */
    'tenant_channel_attribute' => 'space',

    /*
    |--------------------------------------------------------------------------
    | Tenant Database Connection
    |--------------------------------------------------------------------------
    | The Laravel DB connection name used to read tenant database config
    | when building per-tenant PDO connections for the listener.
    */
    'tenant_connection' => 'tenant',

    /*
    |--------------------------------------------------------------------------
    | Listen Sleep
    |--------------------------------------------------------------------------
    | Microseconds to sleep between PostgreSQL LISTEN polling loops.
    */
    'listen_sleep' => 50_000,
];
