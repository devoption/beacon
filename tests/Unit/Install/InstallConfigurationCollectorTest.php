<?php

declare(strict_types=1);

use DevOption\Beacon\Install\InstallConfiguration;
use DevOption\Beacon\Install\InstallConfigurationCollector;
use DevOption\Beacon\Install\IngressProviderRecommendation;

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
        ->and($configuration->updateComposerScripts)->toBeTrue()
        ->and($configuration->secretHandling)->toBe('managed-secret')
        ->and($configuration->existingSecretName)->toBeNull()
        ->and($configuration->ingressProvider)->toBe('none');
});

it('falls back to the base path name when no application name is provided', function (): void {
    $collector = new InstallConfigurationCollector;

    $configuration = $collector->defaultConfiguration(
        basePath: '/srv/apps/acme-platform',
    );

    expect($configuration->applicationName)->toBe('acme-platform');
});

it('uses the recommended ingress provider when one is available from the cluster context', function (): void {
    $collector = new InstallConfigurationCollector;

    $configuration = $collector->defaultConfiguration(
        basePath: '/srv/apps/acme-platform',
        applicationName: 'Acme Platform',
        ingressProviderRecommendation: new IngressProviderRecommendation('traefik', 'rancher-desktop'),
    );

    expect($configuration->ingressProvider)->toBe('traefik');
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

        protected function askIngressProvider(
            string $default,
            ?IngressProviderRecommendation $recommendation = null,
        ): string {
            expect($default)->toBe('none')
                ->and($recommendation)->toBeNull();

            return 'traefik';
        }

        protected function askSecretHandling(string $default): string
        {
            expect($default)->toBe('managed-secret');

            return 'existing-secret';
        }

        protected function askExistingSecretName(string $default): string
        {
            expect($default)->toBe('beacon-app-env');

            return 'shared-platform-env';
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
        'secret_handling' => 'existing-secret',
        'existing_secret_name' => 'shared-platform-env',
        'ingress_provider' => 'traefik',
    ]);
});
