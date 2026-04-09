<?php

declare(strict_types=1);

use DevOption\Beacon\Composer\ComposerScriptsUpdater;
use DevOption\Beacon\Filesystem\FileWriteStatus;
use DevOption\Beacon\Filesystem\SafeFileWriter;
use DevOption\Beacon\Install\InstallConfiguration;

it('adds beacon build and deploy scripts without clobbering unrelated composer scripts', function (): void {
    $updater = new ComposerScriptsUpdater(new SafeFileWriter);
    $configuration = new InstallConfiguration(
        applicationName: 'Beacon Demo',
        runtime: 'octane',
        deploymentTarget: 'docker-and-helm',
        updateComposerScripts: true,
    );

    $manifest = $updater->updateManifest([
        'name' => 'acme/app',
        'scripts' => [
            'test' => '@php artisan test',
        ],
        'scripts-descriptions' => [
            'test' => 'Run the application test suite.',
        ],
    ], $configuration);

    expect($manifest['scripts'])->toBe([
        'test' => '@php artisan test',
        'beacon:build' => 'docker build --file Dockerfile --tag beacon-demo:latest .',
        'beacon:deploy' => '@php artisan beacon:deploy',
    ])
        ->and($manifest['scripts-descriptions'])->toBe([
            'test' => 'Run the application test suite.',
            'beacon:build' => 'Build the Beacon production Docker image.',
            'beacon:deploy' => 'Deploy the Beacon Helm release.',
        ]);
});

it('removes stale beacon deploy entries when the selected target is docker only', function (): void {
    $updater = new ComposerScriptsUpdater(new SafeFileWriter);
    $configuration = new InstallConfiguration(
        applicationName: 'Beacon Demo',
        runtime: 'php-fpm',
        deploymentTarget: 'docker',
        updateComposerScripts: true,
    );

    $manifest = $updater->updateManifest([
        'scripts' => [
            'beacon:build' => 'old build',
            'beacon:deploy' => 'old deploy',
            'lint' => 'vendor/bin/pint',
        ],
        'scripts-descriptions' => [
            'beacon:build' => 'old build description',
            'beacon:deploy' => 'old deploy description',
            'lint' => 'Lint the codebase.',
        ],
    ], $configuration);

    expect($manifest['scripts'])->toBe([
        'lint' => 'vendor/bin/pint',
        'beacon:build' => 'docker build --file Dockerfile --tag beacon-demo:latest .',
    ])
        ->and($manifest['scripts-descriptions'])->toBe([
            'lint' => 'Lint the codebase.',
            'beacon:build' => 'Build the Beacon production Docker image.',
        ]);
});

it('rejects list-shaped composer manifest sections', function (): void {
    $updater = new ComposerScriptsUpdater(new SafeFileWriter);
    $configuration = new InstallConfiguration(
        applicationName: 'Beacon Demo',
        runtime: 'php-fpm',
        deploymentTarget: 'docker-and-helm',
        updateComposerScripts: true,
    );

    expect(fn (): array => $updater->updateManifest([
        'scripts' => [
            '@php artisan test',
            'vendor/bin/pint',
        ],
    ], $configuration))->toThrow(
        RuntimeException::class,
        'Composer manifest section [scripts] must be an object-like map.',
    );
});

it('writes the updated composer manifest back to disk', function (): void {
    $directory = beaconTestTempDirectory();
    $path = $directory.'/composer.json';
    $updater = new ComposerScriptsUpdater(new SafeFileWriter);
    $configuration = new InstallConfiguration(
        applicationName: 'Beacon Demo',
        runtime: 'php-fpm',
        deploymentTarget: 'helm',
        updateComposerScripts: true,
    );

    file_put_contents($path, json_encode([
        'name' => 'acme/app',
        'scripts' => [
            'test' => '@php artisan test',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

    try {
        $result = $updater->write($path, $configuration);
        $manifest = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        expect($result->status)->toBe(FileWriteStatus::Overwritten)
            ->and($manifest['scripts'])->toBe([
                'test' => '@php artisan test',
                'beacon:build' => 'docker build --file Dockerfile --tag beacon-demo:latest .',
                'beacon:deploy' => '@php artisan beacon:deploy',
            ])
            ->and($manifest['scripts-descriptions'])->toBe([
                'beacon:build' => 'Build the Beacon production Docker image.',
                'beacon:deploy' => 'Deploy the Beacon Helm release.',
            ]);
    } finally {
        removeBeaconTestDirectory($directory);
    }
});
