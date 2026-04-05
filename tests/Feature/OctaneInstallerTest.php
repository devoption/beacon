<?php

declare(strict_types=1);

use DevOption\Beacon\Octane\OctaneInstallationStatus;
use DevOption\Beacon\Octane\OctaneInstaller;
use Illuminate\Support\Facades\Process;

it('detects when octane is already declared in the application manifest', function (): void {
    $directory = beaconTestTempDirectory();

    file_put_contents($directory.'/composer.json', json_encode([
        'name' => 'acme/app',
        'require' => [
            'php' => '^8.3',
            'laravel/octane' => '^2.0',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

    Process::fake()->preventStrayProcesses();

    try {
        $result = new OctaneInstaller()->ensureInstalled($directory);

        expect($result->status)->toBe(OctaneInstallationStatus::AlreadyInstalled)
            ->and($result->summary())->toBe('Already available');

        Process::assertNothingRan();
    } finally {
        removeBeaconTestDirectory($directory);
    }
});

it('installs octane with composer when it is missing', function (): void {
    $directory = beaconTestTempDirectory();

    file_put_contents($directory.'/composer.json', json_encode([
        'name' => 'acme/app',
        'require' => [
            'php' => '^8.3',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

    Process::fake([
        '*' => Process::result('Installed laravel/octane.', '', 0),
    ]);

    try {
        $result = new OctaneInstaller()->ensureInstalled($directory);

        expect($result->status)->toBe(OctaneInstallationStatus::Installed)
            ->and($result->summary())->toBe('Installed now');

        Process::assertRan(fn ($process) => $process->path === $directory
            && $process->command === [
                'composer',
                'require',
                'laravel/octane',
                '--no-interaction',
                '--no-progress',
            ]);
    } finally {
        removeBeaconTestDirectory($directory);
    }
});

it('surfaces composer install failures for octane', function (): void {
    $directory = beaconTestTempDirectory();

    file_put_contents($directory.'/composer.json', json_encode([
        'name' => 'acme/app',
        'require' => [
            'php' => '^8.3',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

    Process::fake([
        '*' => Process::result('', 'Composer require failed.', 1),
    ]);

    try {
        expect(fn (): mixed => new OctaneInstaller()->ensureInstalled($directory))
            ->toThrow(RuntimeException::class, 'Unable to install Laravel Octane. Composer require failed.');
    } finally {
        removeBeaconTestDirectory($directory);
    }
});
