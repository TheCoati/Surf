<?php

namespace App\Commands;

use App\Commands\Concerns\RunsProcessCommands;
use LaravelZero\Framework\Commands\Command;

class DefaultCommand extends Command
{
    use RunsProcessCommands;

    protected $signature = '{args*}';

    protected $description = 'Command description';

    public function handle(): void
    {
        $args = $this->argument('args');
        $arguments = join(" ", $args);

        $this->runCommands([
            "docker compose $arguments"
        ]);
    }
}
