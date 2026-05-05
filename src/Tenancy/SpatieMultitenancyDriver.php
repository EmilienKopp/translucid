<?php

namespace Splitstack\Translucid\Tenancy;

use Illuminate\Support\Facades\DB;
use PDO;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Models\Tenant;
use Splitstack\Translucid\Contracts\TenantDriver;

class SpatieMultitenancyDriver implements TenantDriver
{
    public function resolveChannel(): string
    {
        $tenant = Tenant::current();
        $attribute = config('translucid.tenant_channel_attribute', 'space');
        $base = config('translucid.default_channel', 'translucid');

        return $base.'.'.($tenant?->{$attribute} ?? 'unknown');
    }

    public function resolveListenConnections(): array
    {
        /** @var class-string<Tenant> $tenantModel */
        $tenantModel = config('multitenancy.tenant_model', Tenant::class);
        $tenants = $tenantModel::all();
        $connections = [];
        $dbConfig = DB::connection(config('translucid.tenant_connection', 'tenant'))->getConfig();
        $base = config('translucid.default_channel', 'translucid');
        $attribute = config('translucid.tenant_channel_attribute', 'space');

        foreach ($tenants as $tenant) {
            $dsn = "pgsql:host={$dbConfig['host']};dbname={$tenant->database};port={$dbConfig['port']}";
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('LISTEN translucid');
            $key = config('translucid.default_channel', 'translucid').'.'.$tenant->{$attribute};
            $connections[$key] = $pdo;
            // $connections[$tenant->getKey()] = $pdo;
        }

        return $connections;
    }

    public function resolveFeatureScope(mixed $scope): ?string
    {
        if ($scope instanceof IsTenant) {
            $tenant = $scope;
        } elseif (is_string($scope)) {
            return $scope;
        } else {
            $tenant = Tenant::current();
        }

        $key = config('translucid.tenant_channel_attribute', 'id');

        if (! $tenant || ! isset($tenant->{$key})) {
            return null;
        }

        return config('translucid.default_channel', 'translucid').'.'.$tenant->{$key};
    }
}
