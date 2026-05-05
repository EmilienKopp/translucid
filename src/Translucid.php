<?php

namespace Splitstack\Translucid;

use Closure;
use Illuminate\Support\Facades\DB;
use PDO;

class Translucid
{
    protected static ?Closure $channelResolver = null;

    protected static ?Closure $listenConnectionsResolver = null;

    public static function resolveChannelUsing(Closure $callback): void
    {
        static::$channelResolver = $callback;
    }

    public static function resolveChannel(): string
    {
        if (static::$channelResolver) {
            return (static::$channelResolver)();
        }

        return config('translucid.default_channel', 'translucid');
    }

    public static function resolveListenConnectionsUsing(Closure $callback): void
    {
        static::$listenConnectionsResolver = $callback;
    }

    /**
     * @return array<string, PDO> channel => PDO
     */
    public static function resolveListenConnections(): array
    {
        if (static::$listenConnectionsResolver) {
            return (static::$listenConnectionsResolver)();
        }

        $config = DB::connection()->getConfig();
        $dsn = "pgsql:host={$config['host']};dbname={$config['database']};port={$config['port']}";
        $pdo = new PDO($dsn, $config['username'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $channel = config('translucid.default_channel', 'translucid');
        $pdo->exec('LISTEN "'.$channel.'"');

        return [$channel => $pdo];
    }

    /**
     * @param  class-string  $modelClass
     */
    public function observe(string $modelClass): void
    {
        $model = new $modelClass;
        $table = $model->getTable();
        $key = $model->getKeyName();
        $escapedModelClass = addslashes($modelClass);

        DB::unprepared("
            CREATE OR REPLACE FUNCTION translucid_notify_{$table}()
            RETURNS trigger AS \$\$
            DECLARE
                payload jsonb;
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    payload := to_jsonb(OLD);
                ELSE
                    payload := to_jsonb(NEW);
                END IF;

                payload := payload
                    || jsonb_build_object(
                        '_table',      '{$table}',
                        '_key',        '{$key}',
                        '_modelClass', '{$escapedModelClass}',
                        '_event',      lower(TG_OP)
                    );

                PERFORM pg_notify('translucid', payload::text);

                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS translucid_trigger_{$table} ON \"{$table}\";

            CREATE TRIGGER translucid_trigger_{$table}
                AFTER INSERT OR UPDATE OR DELETE ON \"{$table}\"
                FOR EACH ROW EXECUTE FUNCTION translucid_notify_{$table}();
        ");
    }

    /**
     * @param  class-string  $modelClass
     */
    public function unobserve(string $modelClass): void
    {
        $model = new $modelClass;
        $table = $model->getTable();

        DB::unprepared("
            DROP TRIGGER IF EXISTS translucid_trigger_{$table} ON \"{$table}\";
            DROP FUNCTION IF EXISTS translucid_notify_{$table}();
        ");
    }
}
