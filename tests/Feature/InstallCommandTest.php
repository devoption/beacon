<?php

declare(strict_types=1);

use DevOption\Beacon\BeaconServiceProvider;
use Illuminate\Support\Facades\Process;

it('boots the package service provider in testbench', function (): void {
    expect($this->app->getProvider(BeaconServiceProvider::class))->not->toBeNull();
});

it('guides the user through the install skeleton with prompts', function (): void {
    Process::fake([
        '*' => Process::result('Installed laravel/octane.', '', 0),
    ]);

    $this->artisan('beacon:install')
        ->expectsPromptsIntro('Beacon will guide you through the initial production install setup.')
        ->expectsQuestion('What is the application name?', '  Beacon Demo  ')
        ->expectsChoice(
            'Which application runtime should Beacon prepare for?',
            'octane',
            [
                'php-fpm' => 'PHP-FPM',
                'octane' => 'Laravel Octane',
            ]
        )
        ->expectsChoice(
            'Which deployment scaffolding should Beacon plan to generate?',
            'docker-and-helm',
            [
                'docker' => 'Dockerfile',
                'helm' => 'Helm chart',
                'docker-and-helm' => 'Dockerfile and Helm chart',
            ]
        )
        ->expectsConfirmation('Should Beacon plan to update Composer scripts during installation?', 'yes')
        ->expectsOutputToContain('Install skeleton summary')
        ->expectsOutputToContain('Beacon Demo')
        ->expectsOutputToContain('Laravel Octane')
        ->expectsOutputToContain('Dockerfile and Helm chart')
        ->expectsOutputToContain('Plan to update')
        ->expectsOutputToContain('Octane integration')
        ->expectsOutputToContain('Installed now')
        ->expectsOutputToContain('No files were generated in this step.')
        ->expectsPromptsOutro('Beacon collected your installation preferences. File generation will be added in follow-up issues.')
        ->assertSuccessful();

    Process::assertRan(fn ($process) => $process->path === $this->app->basePath()
        && $process->command === [
            'composer',
            'require',
            'laravel/octane',
            '--no-interaction',
            '--no-progress',
        ]);

    expect($this->app->basePath('Dockerfile'))->not->toBeFile();
    expect($this->app->basePath('charts'))->not->toBeDirectory();
});
