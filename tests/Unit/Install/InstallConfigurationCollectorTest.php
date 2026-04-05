<?php

declare(strict_types=1);

use DevOption\Beacon\Install\InstallConfiguration;
use DevOption\Beacon\Install\InstallConfigurationCollector;

it('builds a default install configuration from the application context', function (): void {
    $collector = new InstallConfigurationCollector;

    $configuration = $collector->defaultConfiguration(
        basePath: '/srv/apps/beacon-demo',
        applicationName: ' Beacon Demo ',
    );

    expect($configuration)->toBeInstanceOf(InstallConfiguration::class)
        ->and($configuration->applicationName)->toBe('Beacon Demo')
        ->and($configuration->runtime)->toBe('php-fpm')
        ->and($configuration->deploymentTarget)->toBe('docker-and-helm')
        ->and($configuration->updateComposerScripts)->toBeTrue();
});

it('falls back to the base path name when no application name is provided', function (): void {
    $collector = new InstallConfigurationCollector;

    $configuration = $collector->defaultConfiguration(
        basePath: '/srv/apps/acme-platform',
    );

    expect($configuration->applicationName)->toBe('acme-platform');
});

it('falls back to the base path name when the configured application name is blank', function (): void {
    $collector = new InstallConfigurationCollector;

    $configuration = $collector->defaultConfiguration(
        basePath: '/srv/apps/acme-platform',
        applicationName: '   ',
    );

    expect($configuration->applicationName)->toBe('acme-platform');
});

it('collects prompt answers into a structured install configuration', function (): void {
    $collector = new class extends InstallConfigurationCollector
    {
        protected function askApplicationName(string $default): string
        {
            expect($default)->toBe('Beacon');

            return parent::normalizeApplicationName('  Beacon App  ');
        }

        protected function askRuntime(string $default): string
        {
            expect($default)->toBe('php-fpm');

            return 'octane';
        }

        protected function askDeploymentTarget(string $default): string
        {
            expect($default)->toBe('docker-and-helm');

            return 'helm';
        }

        protected function askUpdateComposerScripts(bool $default): bool
        {
            expect($default)->toBeTrue();

            return false;
        }
    };

    $configuration = $collector->collect(
        basePath: '/srv/apps/beacon',
        applicationName: 'Beacon',
        interactive: true,
    );

    expect($configuration->toArray())->toBe([
        'application_name' => 'Beacon App',
        'runtime' => 'octane',
        'deployment_target' => 'helm',
        'update_composer_scripts' => false,
    ]);
});
