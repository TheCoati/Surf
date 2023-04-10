<?php

namespace App\Commands;

use App\Commands\Concerns\InteractsWithDockerComposeServices;
use LaravelZero\Framework\Commands\Command;

class InstallCommand extends Command
{
    use InteractsWithDockerComposeServices;

    protected $signature = 'install
                {--with= : The services that should be included in the installation}
                {--devcontainer : Create a .devcontainer configuration directory}';

    protected $description = 'Install Radicle Surf\'s default Docker Compose file';

    public function handle(): int
    {
        if ($this->option('with')) {
            $services = $this->option('with') == 'none' ? [] : explode(',', $this->option('with'));
        } elseif ($this->option('no-interaction')) {
            $services = $this->defaultServices;
        } else {
            $services = $this->gatherServicesWithSymfonyMenu();
        }

        if ($invalidServices = array_diff($services, $this->services)) {
            $this->error('Invalid services ['.implode(',', $invalidServices).'].');

            return 1;
        }

        $this->buildDockerCompose($services);
        $this->replaceEnvVariables($services);
        $this->configurePhpUnit();

        if ($this->option('devcontainer')) {
            $this->installDevContainer();
        }

        $this->info('Surf scaffolding installed successfully.');

        $this->prepareInstallation($services);
        return 0;
    }
}
