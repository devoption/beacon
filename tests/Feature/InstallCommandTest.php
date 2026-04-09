<?php

declare(strict_types=1);

use DevOption\Beacon\BeaconServiceProvider;
use DevOption\Beacon\Install\InstallConfiguration;
use DevOption\Beacon\Install\InstallConfigurationCollector;
use Illuminate\Foundation\Application;
use Illuminate\Testing\PendingCommand;
use Illuminate\Support\Facades\Process;

function fakeOctaneComposerRequire(string $directory): void
{
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

            if (file_put_contents(
                $directory.'/composer.json',
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL
            ) === false) {
                throw new RuntimeException(sprintf('Unable to update composer manifest [%s].', $directory.'/composer.json'));
            }

            return Process::result('Installed laravel/octane.', '', 0);
        }

        return Process::result();
    });
}

function expectBeaconInstallPrompts(
    PendingCommand $command,
    string $applicationName = 'Beacon Demo',
    string $runtime = 'octane',
    string $deploymentTarget = 'docker-and-helm',
    bool $updateComposerScripts = true,
): PendingCommand {
    return $command
        ->expectsPromptsIntro('Beacon will guide you through the initial production install setup.')
        ->expectsQuestion('What is the application name?', $applicationName)
        ->expectsChoice(
            'Which application runtime should Beacon prepare for?',
            $runtime,
            [
                'php-fpm' => 'PHP-FPM',
                'octane' => 'Laravel Octane',
            ]
        )
        ->expectsChoice(
            'Which deployment scaffolding should Beacon plan to generate?',
            $deploymentTarget,
            [
                'docker' => 'Dockerfile',
                'helm' => 'Helm chart',
                'docker-and-helm' => 'Dockerfile and Helm chart',
            ]
        )
        ->expectsConfirmation(
            'Should Beacon plan to update Composer scripts during installation?',
            $updateComposerScripts ? 'yes' : 'no'
        );
}

function fakeInstallConfigurationCollector(
    \Illuminate\Contracts\Container\Container $app,
    InstallConfiguration $configuration,
): void {
    $app->instance(InstallConfigurationCollector::class, new class($configuration) extends InstallConfigurationCollector
    {
        public function __construct(private readonly InstallConfiguration $configuration) {}

        public function collect(
            string $basePath,
            ?string $applicationName = null,
            bool $interactive = true,
        ): InstallConfiguration {
            return $this->configuration;
        }
    });
}

function supportsPendingPromptExpectations(): bool
{
    return version_compare(Application::VERSION, '12.0.0', '>=');
}

it('boots the package service provider in testbench', function (): void {
    expect($this->app->getProvider(BeaconServiceProvider::class))->not->toBeNull();
});

it('guides the user through the install skeleton with prompts', function (): void {
    if (! supportsPendingPromptExpectations()) {
        $this->markTestSkipped('Pending command prompt expectations are not reliable on Laravel 11.');
    }

    $directory = beaconTestApplicationDirectory();
    $originalBasePath = $this->app->basePath();

    $this->app->setBasePath($directory);

    fakeOctaneComposerRequire($directory);

    try {
        expectBeaconInstallPrompts($this->artisan('beacon:install'), '  Beacon Demo  ')
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
                'beacon:deploy' => '@php artisan beacon:deploy',
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

it('runs the install workflow from collected configuration', function (): void {
    $directory = beaconTestApplicationDirectory();
    $originalBasePath = $this->app->basePath();

    $this->app->setBasePath($directory);

    fakeInstallConfigurationCollector($this->app, new InstallConfiguration(
        applicationName: 'Beacon Demo',
        runtime: 'octane',
        deploymentTarget: 'docker-and-helm',
        updateComposerScripts: true,
    ));
    fakeOctaneComposerRequire($directory);

    try {
        $this->artisan('beacon:install', ['--no-interaction' => true])
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
                'beacon:deploy' => '@php artisan beacon:deploy',
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

    fakeInstallConfigurationCollector($this->app, new InstallConfiguration(
        applicationName: 'Beacon Demo',
        runtime: 'octane',
        deploymentTarget: 'docker-and-helm',
        updateComposerScripts: true,
    ));
    fakeOctaneComposerRequire($directory);

    $runInstaller = function (): void {
        $this->artisan('beacon:install', ['--no-interaction' => true])->assertSuccessful();
    };

    try {
        $runInstaller();

        $firstDockerfile = file_get_contents($directory.'/Dockerfile');
        $firstChart = file_get_contents($directory.'/charts/beacon-demo/values.yaml');
        $firstManifest = file_get_contents($directory.'/composer.json');

        $this->artisan('beacon:install', ['--no-interaction' => true])
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

    fakeInstallConfigurationCollector($this->app, new InstallConfiguration(
        applicationName: 'Beacon Demo',
        runtime: 'octane',
        deploymentTarget: 'docker',
        updateComposerScripts: false,
    ));

    $this->artisan('beacon:install', ['--no-interaction' => true])
        ->expectsOutputToContain('Beacon installation failed: Unable to install Laravel Octane. Composer require failed.')
        ->assertExitCode(1);
});
