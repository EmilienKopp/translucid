# Translucid

Real-time model change broadcasting for Laravel — powered by PostgreSQL `LISTEN/NOTIFY` or Eloquent lifecycle events.

When a row changes in your database, Translucid fires a Laravel broadcast event on a private channel. Your frontend can subscribe and react instantly, without polling.

---

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13
- PostgreSQL (required for the DB-mode listener)
- `laravel/pennant` ^1.0

## Installation

```bash
composer require splitstack/translucid
```

Publish the config:

```bash
php artisan vendor:publish --tag=translucid-config
```

---

## How it works

Translucid supports two broadcast sources — you pick one (or both) per scope via Pennant feature flags:

| Mode | How events are triggered |
|---|---|
| **From DB** (`TranslucidFromDB`) | PostgreSQL triggers call `pg_notify`. A long-running `translucid:listen` command picks them up and dispatches broadcast events. |
| **From App** (`TranslucidFromApp`) | The `HasTranslucid` trait adds Eloquent observers (`created`, `updated`, `deleted`) that dispatch broadcast events inline. |

Both modes emit the same three events: `TranslucidCreated`, `TranslucidUpdated`, `TranslucidDeleted`.

---

## Getting started

### 1. Install database triggers (DB mode)

Create a migration to register PostgreSQL triggers for each model you want to observe:

```php
use App\Models\Post;
use Illuminate\Database\Migrations\Migration;
use Splitstack\Translucid\Facades\Translucid;

return new class extends Migration
{
    public function up(): void
    {
        Translucid::observe(Post::class);
    }

    public function down(): void
    {
        Translucid::unobserve(Post::class);
    }
};
```

Run migrations, then start the listener:

```bash
php artisan translucid:listen
```

### 2. Add the trait (App mode)

Add `HasTranslucid` to any Eloquent model to broadcast changes via Eloquent observers instead:

```php
use Splitstack\Translucid\Concerns\HasTranslucid;

class Post extends Model
{
    use HasTranslucid;
}
```

### 3. Activate a feature flag

Both modes are gated by Pennant. Activate the flag for the scope you want (a user, tenant, or `null` for global):

```php
use Laravel\Pennant\Feature;
use Splitstack\Translucid\Features\TranslucidFromDB;
use Splitstack\Translucid\Features\TranslucidFromApp;

// Activate DB mode globally
Feature::activate(TranslucidFromDB::class);

// Or activate App mode for a specific user
Feature::for($user)->activate(TranslucidFromApp::class);
```

---

## Broadcast payload

Events broadcast on a **private channel** (default: `translucid`). Each event carries:

```json
{
  "type": "posts",
  "model": "App\\Models\\Post",
  "id": "42",
  "op": "updated",
  "changes": { "title": "New title" }
}
```

The broadcast event name includes the table and record key:

```
translucid.updated.posts.42
translucid.created.posts.42
translucid.deleted.posts.42
```

---

## Configuration

`config/translucid.php`:

```php
return [
    // Base channel name (private channel). Multi-tenant mode appends ".{tenant}".
    'default_channel' => 'translucid',

    // Set a TenantDriver class for per-tenant channel scoping and per-tenant
    // PostgreSQL connections. null = single-tenant.
    'tenant_driver' => null,

    // Attribute on the Tenant model appended to the channel name.
    // Produces channels like "translucid.{tenant.space}".
    'tenant_channel_attribute' => 'space',

    // Laravel DB connection used for per-tenant PDO connections.
    'tenant_connection' => 'tenant',

    // Microseconds to sleep between LISTEN polling loops.
    'listen_sleep' => 50_000,
];
```

---

## Multi-tenancy

### Built-in Spatie driver

If you use `spatie/laravel-multitenancy`, configure the built-in driver:

```php
// config/translucid.php
'tenant_driver' => \Splitstack\Translucid\Tenancy\SpatieMultitenancyDriver::class,
'tenant_channel_attribute' => 'space', // attribute on your Tenant model
'tenant_connection' => 'tenant',       // Laravel DB connection for tenant DBs
```

The listener will open a separate PostgreSQL `LISTEN` connection per tenant and broadcast on scoped channels like `translucid.acme`.

### Custom driver

Implement the `TenantDriver` contract to integrate any tenancy strategy:

```php
use PDO;
use Splitstack\Translucid\Contracts\TenantDriver;

class MyTenantDriver implements TenantDriver
{
    // Returns the broadcast channel for the currently active tenant.
    public function resolveChannel(): string
    {
        return 'translucid.' . MyTenancy::current()->slug;
    }

    // Returns a channel => PDO map. Each PDO must already have LISTEN issued.
    public function resolveListenConnections(): array
    {
        $connections = [];
        foreach (Tenant::all() as $tenant) {
            $pdo = new PDO(/* ... */);
            $pdo->exec('LISTEN translucid');
            $connections['translucid.' . $tenant->slug] = $pdo;
        }
        return $connections;
    }

    // Returns the Pennant scope string for the given tenant.
    public function resolveFeatureScope(mixed $scope): ?string
    {
        return 'translucid.' . $scope->slug;
    }
}
```

Register it in `config/translucid.php`:

```php
'tenant_driver' => MyTenantDriver::class,
```

### Custom channel resolver (without a driver)

For simple cases you can override the channel resolver directly in a service provider:

```php
use Splitstack\Translucid\Translucid;

Translucid::resolveChannelUsing(fn () => 'my-app.' . auth()->id());
```

---

## Artisan commands

| Command | Description |
|---|---|
| `php artisan translucid:listen` | Start polling PostgreSQL for notifications and dispatching broadcast events. Handles `SIGINT`/`SIGTERM` for graceful shutdown. |

---

## Events reference

| Event | Broadcast name | `op` value |
|---|---|---|
| `TranslucidCreated` | `translucid.created.{table}.{id}` | `created` |
| `TranslucidUpdated` | `translucid.updated.{table}.{id}` | `updated` |
| `TranslucidDeleted` | `translucid.deleted.{table}.{id}` | `deleted` |

All three are standard Laravel broadcastable events and work with any broadcasting driver (Reverb, Pusher, Ably, etc.).

---

## Testing

```bash
composer test
```

---

## License

MIT — see [LICENSE.md](LICENSE.md).
