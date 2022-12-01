<?php

namespace Jedymatt\LaravelSailExtended\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'sail:extend-install';

    protected $description = '';

    /**
     * List of services
     *
     * @var string[]
     */
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

        $servicesString = $this->getServicesAsString($dockerCompose);

        $existingServices = $this->getExistingServices($services, $servicesString);

        if (! empty($existingServices)) {
            $services = array_diff($services, $existingServices);

            $this->newLine();
            $this->info('Ignoring existing services...');
        }

        $this->appendServicesToDockerCompose($services);

        $this->newLine();
        $this->info('Services installed successfully.');
    }

    /**
     * @param  string[]  $services
     * @return string
     */
    protected function getServiceStubs(array $services): string
    {
        return collect($services)->map(function ($service) {
            return file_get_contents(__DIR__.'/../../stubs/'.$service.'.stub');
        })->implode('');
    }

    /**
     *  @param  string[]  $services
     *  @return void
     */
    protected function appendServicesToDockerCompose(array $services): void
    {
        $dockerCompose = file_get_contents($this->laravel->basePath('docker-compose.yml'));

        $servicesFromDockerCompose = $this->getServicesAsString($dockerCompose);

        $dockerCompose = str_replace($servicesFromDockerCompose, $servicesFromDockerCompose.$this->getServiceStubs($services), $dockerCompose);

        // write $dockerCompose to file
        file_put_contents($this->laravel->basePath('docker-compose.yml'), $dockerCompose);
    }

    /**
     * Get existing services
     *
     * @param  string[]  $services
     * @param  string  $from
     * @return string[]
     */
    protected function getExistingServices($services, $from): array
    {
        $regex = '/'.implode('|', array_map(function ($service) {
            return '(?<=[^\S]\s)'.$service.'(?=:)'; // Match service name followed by ':' (e.g. mysql:) and preceded only by whitespace
        }, $services)).'/';

        preg_match_all($regex, $from, $matches);

        return array_values($matches[0]);
    }

    protected function getServicesAsString(string $dockerCompose): string
    {
        $regex = '/(?<=^services:$\n)(?:\s+.*\n)*/m';

        preg_match_all($regex, $dockerCompose, $matches);

        return $matches[0][0];
    }
}
