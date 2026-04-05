<?php

declare(strict_types=1);

use DevOption\Beacon\Docker\DockerfileGenerator;
use DevOption\Beacon\Filesystem\ExistingFileBehavior;
use DevOption\Beacon\Filesystem\FileWriteStatus;
use DevOption\Beacon\Filesystem\SafeFileWriter;
use DevOption\Beacon\Install\InstallConfiguration;

it('renders a php-fpm dockerfile from the stub', function (): void {
    $generator = new DockerfileGenerator(new SafeFileWriter);
    $configuration = new InstallConfiguration(
        applicationName: 'Beacon Demo',
        runtime: 'php-fpm',
        deploymentTarget: 'docker-and-helm',
        updateComposerScripts: true,
    );

    $contents = $generator->render($configuration);

    expect($contents)->toContain('FROM php:${PHP_VERSION}-fpm-alpine AS beacon')
        ->and($contents)->toContain('LABEL org.opencontainers.image.title="Beacon Demo"')
        ->and($contents)->toContain('LABEL io.devoption.beacon.runtime="php-fpm"')
        ->and($contents)->toContain('EXPOSE 9000')
        ->and($contents)->toContain('CMD ["php-fpm", "-F"]');
});

it('renders an octane dockerfile from the stub', function (): void {
    $generator = new DockerfileGenerator(new SafeFileWriter);
    $configuration = new InstallConfiguration(
        applicationName: 'Beacon Demo',
        runtime: 'octane',
        deploymentTarget: 'docker',
        updateComposerScripts: false,
    );

    $contents = $generator->render($configuration);

    expect($contents)->toContain('pecl install swoole')
        ->and($contents)->toContain('LABEL io.devoption.beacon.runtime="octane"')
        ->and($contents)->toContain('EXPOSE 8000')
        ->and($contents)->toContain('CMD ["php", "artisan", "octane:start", "--server=swoole", "--host=0.0.0.0", "--port=8000"]');
});

it('escapes the application name for dockerfile label values', function (): void {
    $generator = new DockerfileGenerator(new SafeFileWriter);
    $configuration = new InstallConfiguration(
        applicationName: "Beacon \"Demo\" \\\nNext",
        runtime: 'php-fpm',
        deploymentTarget: 'docker',
        updateComposerScripts: true,
    );

    $contents = $generator->render($configuration);

    expect($contents)->toContain('LABEL org.opencontainers.image.title="')
        ->and($contents)->toContain('Beacon \\"Demo\\"')
        ->and($contents)->toContain('\\\\\\nNext"');
});

it('writes the rendered dockerfile through the safe file writer', function (): void {
    $directory = beaconTestTempDirectory();
    $generator = new DockerfileGenerator(new SafeFileWriter);
    $configuration = new InstallConfiguration(
        applicationName: 'Beacon Demo',
        runtime: 'php-fpm',
        deploymentTarget: 'docker',
        updateComposerScripts: true,
    );

    try {
        $result = $generator->write($directory, $configuration);

        expect($result->status)->toBe(FileWriteStatus::Created)
            ->and($directory.'/Dockerfile')->toBeFile()
            ->and(file_get_contents($directory.'/Dockerfile'))->toContain('LABEL org.opencontainers.image.title="Beacon Demo"');
    } finally {
        removeBeaconTestDirectory($directory);
    }
});

it('supports overwrite behavior when writing a dockerfile', function (): void {
    $directory = beaconTestTempDirectory();
    $generator = new DockerfileGenerator(new SafeFileWriter);
    $configuration = new InstallConfiguration(
        applicationName: 'Beacon Demo',
        runtime: 'octane',
        deploymentTarget: 'docker',
        updateComposerScripts: true,
    );

    file_put_contents($directory.'/Dockerfile', 'FROM php:8.2-cli');

    try {
        $result = $generator->write($directory, $configuration, ExistingFileBehavior::Overwrite);

        expect($result->status)->toBe(FileWriteStatus::Overwritten)
            ->and(file_get_contents($directory.'/Dockerfile'))->toContain('LABEL io.devoption.beacon.runtime="octane"');
    } finally {
        removeBeaconTestDirectory($directory);
    }
});
