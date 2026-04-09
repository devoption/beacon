<?php

declare(strict_types=1);

use DevOption\Beacon\Filesystem\ExistingFileBehavior;
use DevOption\Beacon\Filesystem\FileWriteStatus;
use DevOption\Beacon\Filesystem\SafeFileWriter;
use DevOption\Beacon\Helm\HelmChartGenerator;
use DevOption\Beacon\Install\InstallConfiguration;

it('renders a helm chart scaffold for the php-fpm runtime', function (): void {
    $generator = new HelmChartGenerator(new SafeFileWriter);
    $configuration = new InstallConfiguration(
        applicationName: 'Beacon Demo',
        runtime: 'php-fpm',
        deploymentTarget: 'helm',
        updateComposerScripts: true,
    );

    $files = $generator->renderFiles($configuration);

    expect(array_keys($files))->toBe([
        'Chart.yaml',
        'values.yaml',
        'values.local.yaml',
        'values.local.secrets.example.yaml',
        'values.staging.yaml',
        'values.staging.secrets.example.yaml',
        'values.production.yaml',
        'values.production.secrets.example.yaml',
        'templates/_helpers.tpl',
        'templates/deployment.yaml',
        'templates/secret.yaml',
        'templates/service.yaml',
        'templates/ingress.yaml',
    ])
        ->and($files['Chart.yaml'])->toContain('name: beacon-demo')
        ->and($files['Chart.yaml'])->toContain('Beacon Demo')
        ->and($files['values.yaml'])->toContain('runtime: php-fpm')
        ->and($files['values.yaml'])->toContain('port: 9000')
        ->and($files['values.yaml'])->toContain('provider: none')
        ->and($files['values.yaml'])->toContain('create: true')
        ->and($files['values.local.yaml'])->toContain('APP_ENV: local')
        ->and($files['values.local.secrets.example.yaml'])->toContain('# APP_KEY: base64:replace-me')
        ->and($files['values.staging.yaml'])->toContain('APP_ENV: staging')
        ->and($files['values.production.yaml'])->toContain('APP_ENV: production')
        ->and($files['templates/deployment.yaml'])->toContain('containerPort: {{ .Values.service.port }}')
        ->and($files['templates/secret.yaml'])->toContain('kind: Secret');
});

it('renders octane-specific helm values', function (): void {
    $generator = new HelmChartGenerator(new SafeFileWriter);
    $configuration = new InstallConfiguration(
        applicationName: 'Beacon Demo',
        runtime: 'octane',
        deploymentTarget: 'docker-and-helm',
        updateComposerScripts: false,
    );

    $files = $generator->renderFiles($configuration);

    expect($files['values.yaml'])->toContain('runtime: octane')
        ->and($files['values.yaml'])->toContain('port: 8000');
});

it('renders ingress provider values from the selected install strategy', function (): void {
    $generator = new HelmChartGenerator(new SafeFileWriter);
    $configuration = new InstallConfiguration(
        applicationName: 'Beacon Demo',
        runtime: 'octane',
        deploymentTarget: 'docker-and-helm',
        updateComposerScripts: false,
        ingressProvider: 'traefik',
    );

    $files = $generator->renderFiles($configuration);

    expect($files['values.yaml'])->toContain('provider: traefik')
        ->and($files['values.yaml'])->toContain('enabled: true')
        ->and($files['values.yaml'])->toContain('className: "traefik"');
});

it('renders existing secret references when the user chooses an external kubernetes secret', function (): void {
    $generator = new HelmChartGenerator(new SafeFileWriter);
    $configuration = new InstallConfiguration(
        applicationName: 'Beacon Demo',
        runtime: 'php-fpm',
        deploymentTarget: 'helm',
        updateComposerScripts: true,
        secretHandling: 'existing-secret',
        existingSecretName: 'shared-platform-env',
    );

    $files = $generator->renderFiles($configuration);

    expect($files['values.yaml'])->toContain('create: false')
        ->and($files['values.yaml'])->toContain('existingSecretName: "shared-platform-env"');
});

it('truncates long chart names to helm-friendly lengths', function (): void {
    $generator = new HelmChartGenerator(new SafeFileWriter);
    $configuration = new InstallConfiguration(
        applicationName: 'Beacon Demo Platform For Very Long Production Installations Across Multiple Regions',
        runtime: 'php-fpm',
        deploymentTarget: 'helm',
        updateComposerScripts: true,
    );

    $files = $generator->renderFiles($configuration);

    expect($generator->chartPath('/tmp/app', $configuration))->toBe(
        '/tmp/app/charts/beacon-demo-platform-for-very-long-production-installations-acr'
    )
        ->and($files['Chart.yaml'])->toContain('name: beacon-demo-platform-for-very-long-production-installations-acr');
});

it('escapes the application name for yaml double quoted strings', function (): void {
    $generator = new HelmChartGenerator(new SafeFileWriter);
    $configuration = new InstallConfiguration(
        applicationName: "Beacon \"Demo\" \\\nNext",
        runtime: 'php-fpm',
        deploymentTarget: 'helm',
        updateComposerScripts: true,
    );

    $files = $generator->renderFiles($configuration);

    expect($files['Chart.yaml'])->toContain('Beacon \\"Demo\\"')
        ->and($files['Chart.yaml'])->toContain('\\\\nNext"');
});

it('writes the helm chart scaffold through the safe file writer', function (): void {
    $directory = beaconTestTempDirectory();
    $generator = new HelmChartGenerator(new SafeFileWriter);
    $configuration = new InstallConfiguration(
        applicationName: 'Beacon Demo',
        runtime: 'php-fpm',
        deploymentTarget: 'helm',
        updateComposerScripts: true,
    );

    try {
        $result = $generator->write($directory, $configuration);

        expect($result->chartPath)->toBe($directory.'/charts/beacon-demo')
            ->and($result->files['Chart.yaml']->status)->toBe(FileWriteStatus::Created)
            ->and($directory.'/charts/beacon-demo/Chart.yaml')->toBeFile()
            ->and(file_get_contents($directory.'/charts/beacon-demo/values.yaml'))->toContain('port: 9000')
            ->and($directory.'/charts/beacon-demo/values.local.yaml')->toBeFile()
            ->and($directory.'/charts/beacon-demo/values.local.secrets.example.yaml')->toBeFile()
            ->and($directory.'/charts/beacon-demo/values.staging.yaml')->toBeFile()
            ->and($directory.'/charts/beacon-demo/values.staging.secrets.example.yaml')->toBeFile()
            ->and($directory.'/charts/beacon-demo/values.production.yaml')->toBeFile();
    } finally {
        removeBeaconTestDirectory($directory);
    }
});

it('supports overwrite behavior when writing the helm chart scaffold', function (): void {
    $directory = beaconTestTempDirectory();
    $generator = new HelmChartGenerator(new SafeFileWriter);
    $configuration = new InstallConfiguration(
        applicationName: 'Beacon Demo',
        runtime: 'octane',
        deploymentTarget: 'helm',
        updateComposerScripts: true,
    );

    mkdir($directory.'/charts/beacon-demo/templates', 0755, true);
    file_put_contents($directory.'/charts/beacon-demo/values.yaml', 'service:'."\n".'  port: 1234');

    try {
        $result = $generator->write($directory, $configuration, ExistingFileBehavior::Overwrite);

        expect($result->files['values.yaml']->status)->toBe(FileWriteStatus::Overwritten)
            ->and(file_get_contents($directory.'/charts/beacon-demo/values.yaml'))->toContain('port: 8000');
    } finally {
        removeBeaconTestDirectory($directory);
    }
});
