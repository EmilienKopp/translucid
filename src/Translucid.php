<?php

namespace Splitstack\Translucid;

class Translucid
{
    /**
     * @param  class-string<Model>  $modelClass
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
     * @param  class-string<Model>  $modelClass
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
