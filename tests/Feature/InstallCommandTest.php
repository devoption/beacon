<?php

declare(strict_types=1);

use DevOption\Beacon\BeaconServiceProvider;
use Illuminate\Support\Facades\Process;

it('boots the package service provider in testbench', function (): void {
    expect($this->app->getProvider(BeaconServiceProvider::class))->not->toBeNull();
});

it('guides the user through the install skeleton with prompts', function (): void {
    $directory = beaconTestApplicationDirectory();
    $originalBasePath = $this->app->basePath();

    $this->app->setBasePath($directory);

    Process::fake(function ($process) use ($directory) {
        if ($process->command === [
            'composer',
            'require',
            'laravel/octane',
            '--no-interaction',
            '--no-progress',
        ]) {
            $manifest = json_decode((string) file_get_contents($directory.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
            $manifest['require']['laravel/octane'] = '^2.0';

            file_put_contents(
                $directory.'/composer.json',
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL
            );

            return Process::result('Installed laravel/octane.', '', 0);
        }

        return Process::result();
    });

    try {
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
            ->expectsOutputToContain('Generated artifacts')
            ->expectsOutputToContain('Beacon installation completed.')
            ->assertSuccessful();

        Process::assertRanTimes(fn ($process) => $process->path === $directory
            && $process->command === [
                'composer',
                'require',
                'laravel/octane',
                '--no-interaction',
                '--no-progress',
            ], 1);

        $manifest = json_decode((string) file_get_contents($directory.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);

        expect($directory.'/Dockerfile')->toBeFile()
            ->and(file_get_contents($directory.'/Dockerfile'))->toContain('LABEL io.devoption.beacon.runtime="octane"')
            ->and($directory.'/charts/beacon-demo/Chart.yaml')->toBeFile()
            ->and(file_get_contents($directory.'/charts/beacon-demo/values.yaml'))->toContain('runtime: octane')
            ->and($manifest['require'])->toHaveKey('laravel/octane')
            ->and($manifest['scripts'])->toMatchArray([
                'test' => '@php artisan test',
                'beacon:build' => 'docker build --file Dockerfile --tag beacon-demo:latest .',
                'beacon:deploy' => 'helm upgrade --install beacon-demo ./charts/beacon-demo --namespace default --create-namespace',
            ])
            ->and($manifest['scripts-descriptions'])->toMatchArray([
                'beacon:build' => 'Build the Beacon production Docker image.',
                'beacon:deploy' => 'Deploy the Beacon Helm release.',
            ]);
    } finally {
        $this->app->setBasePath($originalBasePath);
        removeBeaconTestDirectory($directory);
    }
});

it('is idempotent when the installer is run twice with the same answers', function (): void {
    $directory = beaconTestApplicationDirectory();
    $originalBasePath = $this->app->basePath();

    $this->app->setBasePath($directory);

    Process::fake(function ($process) use ($directory) {
        if ($process->command === [
            'composer',
            'require',
            'laravel/octane',
            '--no-interaction',
            '--no-progress',
        ]) {
            $manifest = json_decode((string) file_get_contents($directory.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
            $manifest['require']['laravel/octane'] = '^2.0';

            file_put_contents(
                $directory.'/composer.json',
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL
            );

            return Process::result('Installed laravel/octane.', '', 0);
        }

        return Process::result();
    });

    $runInstaller = function (): void {
        $this->artisan('beacon:install')
            ->expectsPromptsIntro('Beacon will guide you through the initial production install setup.')
            ->expectsQuestion('What is the application name?', 'Beacon Demo')
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
            ->assertSuccessful();
    };

    try {
        $runInstaller();

        $firstDockerfile = file_get_contents($directory.'/Dockerfile');
        $firstChart = file_get_contents($directory.'/charts/beacon-demo/values.yaml');
        $firstManifest = file_get_contents($directory.'/composer.json');

        $this->artisan('beacon:install')
            ->expectsPromptsIntro('Beacon will guide you through the initial production install setup.')
            ->expectsQuestion('What is the application name?', 'Beacon Demo')
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
            ->expectsOutputToContain('Already available')
            ->expectsOutputToContain('Unchanged')
            ->assertSuccessful();

        Process::assertRanTimes(fn ($process) => $process->path === $directory
            && $process->command === [
                'composer',
                'require',
                'laravel/octane',
                '--no-interaction',
                '--no-progress',
            ], 1);

        expect(file_get_contents($directory.'/Dockerfile'))->toBe($firstDockerfile)
            ->and(file_get_contents($directory.'/charts/beacon-demo/values.yaml'))->toBe($firstChart)
            ->and(file_get_contents($directory.'/composer.json'))->toBe($firstManifest);
    } finally {
        $this->app->setBasePath($originalBasePath);
        removeBeaconTestDirectory($directory);
    }
});

it('fails gracefully when octane installation cannot be completed', function (): void {
    Process::fake([
        '*' => Process::result('', 'Composer require failed.', 1),
    ]);

    $this->artisan('beacon:install')
        ->expectsPromptsIntro('Beacon will guide you through the initial production install setup.')
        ->expectsQuestion('What is the application name?', 'Beacon Demo')
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
            'docker',
            [
                'docker' => 'Dockerfile',
                'helm' => 'Helm chart',
                'docker-and-helm' => 'Dockerfile and Helm chart',
            ]
        )
        ->expectsConfirmation('Should Beacon plan to update Composer scripts during installation?', 'no')
        ->expectsOutputToContain('Failed to ensure Octane is available: Unable to install Laravel Octane. Composer require failed.')
        ->assertExitCode(1);
});
