<?php

namespace Jedymatt\LaravelSailExtended\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'sail:extend-install';

    protected $description = '';

    protected $services = [
        'phpmyadmin',
    ];

    public function handle()
    {
        $services = $this->choice('Which service do you want to install?', $this->services, null, 1, true);

        if (empty($services)) {
            $this->error('No service selected.');

            return;
        }

        $this->newLine();
        $this->info('Installing services...');
        $this->comment('['.implode(', ', $services).']');

        $dockerCompose = file_get_contents($this->laravel->basePath('docker-compose.yml'));

        $servicesString = $this->getStringServicesFromDockerCompose($dockerCompose);

        $existingServices = $this->getExistingServices($services, $servicesString);

        if (! empty($existingServices)) {
            $services = array_diff($services, $existingServices);

            $this->newLine();
            $this->info('Ignoring existing services...');
        }

        $this->addServicesToDockerCompose($services);

        $this->newLine();
        $this->info('Services installed successfully.');
    }

    protected function transformServices(array $services, string $from): string
    {
        $stubs = collect($services)->map(function ($service) {
            return file_get_contents(__DIR__.'/../../stubs/'.$service.'.stub');
        })->implode('');

        return $from.$stubs;
    }

    protected function addServicesToDockerCompose($services)
    {
        $dockerCompose = file_get_contents($this->laravel->basePath('docker-compose.yml'));

        $servicesFromDockerCompose = $this->getStringServicesFromDockerCompose($dockerCompose);

        $finalServices = $this->transformServices($services, $servicesFromDockerCompose);

        $dockerCompose = str_replace($servicesFromDockerCompose, $finalServices, $dockerCompose);

        // write $dockerCompose to file
        file_put_contents($this->laravel->basePath('docker-compose.yml'), $dockerCompose);
    }

    protected function getExistingServices($services, $from)
    {
        $regex = '/'.implode('|', array_map(function ($service) {
            return '(?<=[^\S]\s)'.$service.'(?=:)'; // Match service name followed by ':' (e.g. mysql:) and preceded only by whitespace
        }, $services)).'/';

        preg_match_all($regex, $from, $matches);

        return array_values($matches[0]);
    }

    /**
     *
     * @param string $dockerCompose
     * @return string
     */
    protected function getStringServicesFromDockerCompose($dockerCompose)
    {
        $regex = '/services:\n(?:\s+.*\n)*/';

        preg_match_all($regex, $dockerCompose, $matches);

        return $matches[0][0];
    }
}
