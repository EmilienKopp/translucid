<?php

namespace Splitstack\Translucid\Contracts;

use PDO;

interface TenantDriver
{
    /**
     * Resolve the broadcast channel name for the current tenant.
     *
     * Called per-request (or per-event dispatch), so the return value should
     * reflect the tenant that is active at call time, not at construction time.
     *
     * Example return value: "translucid.acme"
     */
    public function resolveChannel(): string;

    /**
     * Return a map of channel name => PDO connection for every tenant that
     * should be listened to by the `translucid:listen` command.
     *
     * Each PDO connection must already have LISTEN issued on the PostgreSQL
     * notification channel before being returned.
     *
     * @return array<string, PDO> channel => PDO
     */
    public function resolveListenConnections(): array;

    public function resolveFeatureScope(mixed $scope): ?string;
}
