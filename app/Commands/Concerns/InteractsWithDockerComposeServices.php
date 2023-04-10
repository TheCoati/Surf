<?php

namespace App\Commands\Concerns;

use Symfony\Component\Yaml\Yaml;

trait InteractsWithDockerComposeServices
{
    use RunsProcessCommands;

    protected array $services = [
        'mysql',
        'pgsql',
        'mariadb',
        'redis',
        'memcached',
        'meilisearch',
        'minio',
        'mailpit',
        'selenium',
        'soketi',
    ];

    protected array $defaultServices = [
        'mysql',
        'mailpit',
    ];

    protected function gatherServicesWithSymfonyMenu(): array|string
    {
        return $this->choice('Which services would you like to install?', $this->services, 0, null, true);
    }

    protected function buildDockerCompose(array $services): void
    {
        $composePath = base_path('docker-compose.yml');

        $compose = file_exists($composePath)
            ? Yaml::parseFile($composePath)
            : Yaml::parse(file_get_contents(__DIR__ . '/../../../resources/stubs/docker-compose.stub'));

        if (! array_key_exists('radicle.test', $compose['services'])) {
            $this->warn('Couldn\'t find the radicle.test service. Make sure you add ['.implode(',', $services).'] to the depends_on config.');
        } else {
            $compose['services']['radicle.test']['depends_on'] = collect($compose['services']['radicle.test']['depends_on'] ?? [])
                ->merge($services)
                ->unique()
                ->values()
                ->all();
        }

        collect($services)
            ->filter(function ($service) use ($compose) {
                return ! array_key_exists($service, $compose['services'] ?? []);
            })->each(function ($service) use (&$compose) {
                $compose['services'][$service] = Yaml::parseFile(__DIR__ . "/../../../resources/stubs/{$service}.stub")[$service];
            });

        collect($services)
            ->filter(function ($service) {
                return in_array($service, ['mysql', 'pgsql', 'mariadb', 'redis', 'meilisearch', 'minio']);
            })->filter(function ($service) use ($compose) {
                return ! array_key_exists($service, $compose['volumes'] ?? []);
            })->each(function ($service) use (&$compose) {
                $compose['volumes']["surf-{$service}"] = ['driver' => 'local'];
            });

        if (empty($compose['volumes'])) {
            unset($compose['volumes']);
        }

        if (in_array('selenium', $services) && in_array(php_uname('m'), ['arm64', 'aarch64'])) {
            $compose['services']['selenium']['image'] = 'seleniarm/standalone-chromium';
        }

        file_put_contents('docker-compose.yml', Yaml::dump($compose, Yaml::DUMP_OBJECT_AS_MAP));
    }

    protected function replaceEnvVariables(array $services): void
    {
        $environment = file_get_contents('.env');

        if (in_array('pgsql', $services)) {
            $environment = str_replace('DB_CONNECTION=mysql', "DB_CONNECTION=pgsql", $environment);
            $environment = str_replace('DB_HOST=127.0.0.1', "DB_HOST=pgsql", $environment);
            $environment = str_replace('DB_PORT=3306', "DB_PORT=5432", $environment);
        } elseif (in_array('mariadb', $services)) {
            $environment = str_replace('DB_HOST=127.0.0.1', "DB_HOST=mariadb", $environment);
        } else {
            $environment = str_replace('DB_HOST=127.0.0.1', "DB_HOST=mysql", $environment);
        }

        $environment = str_replace('DB_USERNAME=root', "DB_USERNAME=surf", $environment);
        $environment = preg_replace("/DB_PASSWORD=(.*)/", "DB_PASSWORD=password", $environment);

        if (in_array('memcached', $services)) {
            $environment = str_replace('MEMCACHED_HOST=127.0.0.1', 'MEMCACHED_HOST=memcached', $environment);
        }

        if (in_array('redis', $services)) {
            $environment = str_replace('REDIS_HOST=127.0.0.1', 'REDIS_HOST=redis', $environment);
        }

        if (in_array('meilisearch', $services)) {
            $environment .= "\nSCOUT_DRIVER=meilisearch";
            $environment .= "\nMEILISEARCH_HOST=http://meilisearch:7700\n";
        }

        if (in_array('soketi', $services)) {
            $environment = preg_replace("/^BROADCAST_DRIVER=(.*)/m", "BROADCAST_DRIVER=pusher", $environment);
            $environment = preg_replace("/^PUSHER_APP_ID=(.*)/m", "PUSHER_APP_ID=app-id", $environment);
            $environment = preg_replace("/^PUSHER_APP_KEY=(.*)/m", "PUSHER_APP_KEY=app-key", $environment);
            $environment = preg_replace("/^PUSHER_APP_SECRET=(.*)/m", "PUSHER_APP_SECRET=app-secret", $environment);
            $environment = preg_replace("/^PUSHER_HOST=(.*)/m", "PUSHER_HOST=soketi", $environment);
            $environment = preg_replace("/^PUSHER_PORT=(.*)/m", "PUSHER_PORT=6001", $environment);
            $environment = preg_replace("/^PUSHER_SCHEME=(.*)/m", "PUSHER_SCHEME=http", $environment);
            $environment = preg_replace("/^VITE_PUSHER_HOST=(.*)/m", "VITE_PUSHER_HOST=localhost", $environment);
        }

        if (in_array('mailpit', $services)) {
            $environment = preg_replace("/^MAIL_HOST=(.*)/m", "MAIL_HOST=mailpit", $environment);
        }

        file_put_contents('.env', $environment);
    }

    protected function configurePhpUnit(): void
    {
        if (! file_exists($path = $this->laravel->basePath('phpunit.xml'))) {
            $path = $this->laravel->basePath('phpunit.xml.dist');
        }

        $phpunit = file_get_contents($path);

        $phpunit = preg_replace('/^.*DB_CONNECTION.*\n/m', '', $phpunit);
        $phpunit = str_replace('<!-- <env name="DB_DATABASE" value=":memory:"/> -->', '<env name="DB_DATABASE" value="testing"/>', $phpunit);

        file_put_contents($this->laravel->basePath('phpunit.xml'), $phpunit);
    }

    protected function installDevContainer(): void
    {
        if (! is_dir($this->laravel->basePath('.devcontainer'))) {
            mkdir($this->laravel->basePath('.devcontainer'), 0755, true);
        }

        file_put_contents(
            $this->laravel->basePath('.devcontainer/devcontainer.json'),
            file_get_contents(__DIR__.'/../../../resources/stubs/devcontainer.stub')
        );

        $environment = file_get_contents('.env');

        $environment .= "\nWWWGROUP=1000";
        $environment .= "\nWWWUSER=1000\n";

        file_put_contents('.env', $environment);
    }

    protected function prepareInstallation($services): void
    {
        // Ensure docker is installed...
        if ($this->runCommands(['docker info > /dev/null 2>&1']) !== 0) {
            return;
        }

        if (count($services) > 0) {
            $status = $this->runCommands([
                'docker compose pull '.implode(' ', $services),
            ]);

            if ($status === 0) {
                $this->info('Surf images installed successfully.');
            }
        }

        $status = $this->runCommands([
            'docker compose build',
        ]);

        if ($status === 0) {
            $this->info('Surf build successful.');
        }
    }
}
