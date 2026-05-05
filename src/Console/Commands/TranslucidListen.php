<?php

namespace Splitstack\Translucid\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Pennant\Feature;
use PDO;
use Splitstack\Translucid\Events\TranslucidCreated;
use Splitstack\Translucid\Events\TranslucidDeleted;
use Splitstack\Translucid\Events\TranslucidUpdated;
use Splitstack\Translucid\Features\TranslucidFromDB;
use Splitstack\Translucid\Translucid;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;

class TranslucidListen extends Command
{
    const NON_BLOCKING = 0;

    const CHANNEL = 'translucid';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translucid:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        intro('Listening for PostgreSQL notifications.');
        $connections = Translucid::resolveListenConnections();

        info('Listening on tenants: '.implode(', ', array_keys($connections)));

        $running = true;
        if (extension_loaded('pcntl')) {
            pcntl_signal(SIGINT, function () use (&$running) {
                $running = false;
            });
            pcntl_signal(SIGTERM, function () use (&$running) {
                $running = false;
            });
        }

        while ($running) {
            foreach ($connections as $connection => $tenantPdo) {

                if (config('translucid.tenant_driver')) {
                    $driver = app(config('translucid.tenant_driver'));
                    if (method_exists($driver, 'resolveFeatureScope')) {
                        $scope = $this->resolveFeatureScopeUsing(fn () => $driver->resolveFeatureScope($connection));
                        if (! Feature::for($scope)->active(TranslucidFromDB::class)) {
                            continue;
                        }
                    }
                }

                $notif = $tenantPdo->pgsqlGetNotify(
                    PDO::FETCH_ASSOC,
                    self::NON_BLOCKING
                );

                if ($notif) {
                    $this->info('Received notification: '.json_encode($notif).' on channel: '.$connection);
                    $payload = $this->parsePayload($notif['payload']);
                    $event = $payload['metadata']['event'] ?? null;

                    $eventClass = match ($event) {
                        'updated' => TranslucidUpdated::class,
                        'created' => TranslucidCreated::class,
                        'deleted' => TranslucidDeleted::class,
                        default => TranslucidUpdated::class,
                    };

                    event(new $eventClass(
                        $payload['data'],
                        channel: $connection,
                        table: $payload['metadata']['table'],
                        modelClass: $payload['metadata']['modelClass'],
                        keyColumn: $payload['metadata']['key'] ?? null,
                    ));
                }
            }
            usleep(config('translucid.listen_sleep', 50_000)); // always be kind to our CPU
            if (extension_loaded('pcntl')) {
                pcntl_signal_dispatch();
            }
        }

        outro('Finished listening for notifications.');

        return 0;
    }

    private function parsePayload(string $payload): array
    {
        $data = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Failed to parse payload: '.$payload);

            return [];
        }
        $table = $data['_table'] ?? null;
        $key = $data['_key'] ?? null;
        $modelClass = $data['_modelClass'] ?? null;
        $event = $data['_event'] ?? null;

        unset($data['_table'], $data['_key'], $data['_modelClass'], $data['_event']);

        return [
            'data' => $data,
            'metadata' => [
                'table' => $table,
                'key' => $key,
                'modelClass' => $modelClass,
                'event' => $event,
            ],
        ];
    }

    public function resolveFeatureScopeUsing(?callable $scope): ?string
    {
        return $scope ? $scope() : null;
    }
}
