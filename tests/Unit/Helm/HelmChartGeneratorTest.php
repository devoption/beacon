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
        'templates/_helpers.tpl',
        'templates/deployment.yaml',
        'templates/service.yaml',
        'templates/ingress.yaml',
    ])
        ->and($files['Chart.yaml'])->toContain('name: beacon-demo')
        ->and($files['Chart.yaml'])->toContain('Beacon Demo')
        ->and($files['values.yaml'])->toContain('runtime: php-fpm')
        ->and($files['values.yaml'])->toContain('port: 9000')
        ->and($files['templates/deployment.yaml'])->toContain('containerPort: {{ .Values.service.port }}');
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
            ->and(file_get_contents($directory.'/charts/beacon-demo/values.yaml'))->toContain('port: 9000');
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
