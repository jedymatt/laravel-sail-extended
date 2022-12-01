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

        $this->info('Installing services...');
        $this->comment('[' . implode(', ', $services) . ']');

        $this->addServicesToDockerCompose($services);

        $this->newLine();
        $this->info('Services installed successfully.');
    }

    protected function addServicesToDockerCompose($services)
    {
        $dockerCompose = file_get_contents($this->laravel->basePath('docker-compose.yml'));

        $existingServices = $this->getExistingServicesFromDockerCompose($services, $dockerCompose);

        if (count($existingServices) !== 0) {
            $services = array_diff($services, $existingServices);
            
            $this->newLine();
            $this->info('Ignoring existing services...');
        }

        $regex = '/services:\n(?:\s+.*\n)*/';

        preg_match_all($regex, $dockerCompose, $matches);

        $servicesFromDockerCompose = $matches[0][0];

        $stubs = collect($services)->map(function ($service) {
            return file_get_contents(__DIR__ . "/../../stubs/{$service}.stub");
        })->implode('');

        $servicesFromDockerCompose .= $stubs;

        // replace $servicesFromDockerCompose in $dockerCompose
        $dockerCompose = preg_replace($regex, $servicesFromDockerCompose, $dockerCompose);

        // write $dockerCompose to file
        file_put_contents($this->laravel->basePath('docker-compose.yml'), $dockerCompose);
    }

    protected function getExistingServicesFromDockerCompose($services, $dockerCompose)
    {
        $regex = '/' . implode('|', array_map(function ($service) {
            return '(?<=[^\S]\s)' . $service . '(?=:)'; // Match service name followed by ':' (e.g. mysql:) and preceded only by whitespace
        }, $services)) . '/';

        preg_match_all($regex, $dockerCompose, $matches);

        return array_values($matches[0]);
    }
}