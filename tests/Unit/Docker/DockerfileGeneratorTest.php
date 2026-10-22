<?php

declare(strict_types=1);

use DevOption\Beacon\Docker\DockerfileGenerator;
use DevOption\Beacon\Filesystem\ExistingFileBehavior;
use DevOption\Beacon\Filesystem\FileWriteStatus;
use DevOption\Beacon\Filesystem\SafeFileWriter;
use DevOption\Beacon\Install\InstallConfiguration;
use Symfony\Component\Process\Process;

it('renders a php-fpm dockerfile from the stub', function (): void {
    $generator = new DockerfileGenerator(new SafeFileWriter);
    $configuration = new InstallConfiguration(
        applicationName: 'Beacon Demo',
        runtime: 'php-fpm',
        deploymentTarget: 'docker-and-helm',
        updateComposerScripts: true,
    );

    $contents = $generator->render($configuration);

    expect($contents)->toContain('ARG PHP_VERSION=8.4.27')
        ->and($contents)->toContain('FROM php:${PHP_VERSION}-fpm-alpine AS beacon')
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
    $matched = preg_match('/RUN apk add --no-cache\s+(.+?)\s+&& pecl install swoole/s', $contents, $matches);

    expect($matched)->toBe(1);

    $apkAddBlock = $matches[1];

    expect($contents)->toContain('ARG PHP_VERSION=8.4.27')
        ->and($apkAddBlock)->toContain('brotli-dev')
        ->and($apkAddBlock)->toContain('openssl-dev')
        ->and($contents)->toContain('pecl install swoole')
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

    file_put_contents($directory.'/Dockerfile', 'FROM php:8.3-cli');

    try {
        $result = $generator->write($directory, $configuration, ExistingFileBehavior::Overwrite);

        expect($result->status)->toBe(FileWriteStatus::Overwritten)
            ->and(file_get_contents($directory.'/Dockerfile'))->toContain('LABEL io.devoption.beacon.runtime="octane"');
    } finally {
        removeBeaconTestDirectory($directory);
    }
});

it('rejects vulnerable PHP runtime overrides', function (string $runtime, int $version, int $expectedExitCode): void {
    $generator = new DockerfileGenerator(new SafeFileWriter);
    $configuration = new InstallConfiguration(
        applicationName: 'Beacon Demo',
        runtime: $runtime,
        deploymentTarget: 'docker',
        updateComposerScripts: false,
    );

    $contents = $generator->render($configuration);
    $matched = preg_match("/RUN php -r '([^']+)'/", $contents, $matches);
    expect($matched)->toBe(1);

    $process = new Process([PHP_BINARY, '-r', str_replace('PHP_VERSION_ID', (string) $version, $matches[1])]);
    $process->run();

    expect($process->getExitCode())->toBe($expectedExitCode);
})->with(['php-fpm', 'octane'])->with([
    'before 8.3 fix' => [80335, 1],
    '8.3 fix' => [80336, 0],
    'before 8.4 fix' => [80426, 1],
    '8.4 fix' => [80427, 0],
    'later 8.4 patch' => [80428, 0],
    'before 8.5 fix' => [80511, 1],
    '8.5 fix' => [80512, 0],
    'unsupported 8.1' => [80199, 1],
]);
