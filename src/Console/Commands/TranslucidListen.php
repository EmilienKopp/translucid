<?php

namespace Splitstack\Translucid\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;

class TranslucidListen extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translucid:listen
        {channels?* : The PostgreSQL channel to listen on}
        {--o|once : Listen for a single notification and then exit}
        {--t|timeout=10 : Timeout in seconds for waiting for notifications}
    ';

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
        intro('Listening for PostgreSQL notifications...');
        $channels = array_unique(
            array_merge(
                $this->argument('channels') ?? [],
                config('translucid.channels', ['translucid'])
            )
        );

        info('Listening on channels: '.implode(', ', $channels));

        $pdo = DB::connection()->getPdo();
        foreach ($channels as $channel) {
            $pdo->exec("LISTEN $channel");
        }
        $timeout = (int) $this->option('timeout');
        while (true) {
            $notif = $pdo->pgsqlGetNotify(PDO::FETCH_ASSOC, $timeout * 1000);
            if ($notif) {
                $this->info('Received notification: '.json_encode($notif));
                if ($this->option('once')) {
                    break;
                }
            }
        }

        outro('Finished listening for notifications.');

        return 0;
    }
}
