<?php

declare(strict_types=1);

use DevOption\Beacon\Tests\TestCase;

uses(TestCase::class)->in('Feature');

function beaconTestTempDirectory(): string
{
    $directory = sys_get_temp_dir().'/beacon-tests/'.bin2hex(random_bytes(10));

    if (! mkdir($directory, 0755, true) && ! is_dir($directory)) {
        throw new RuntimeException(sprintf('Unable to create test directory [%s].', $directory));
    }

    return $directory;
}

function removeBeaconTestDirectory(string $directory): void
{
    if (! is_dir($directory)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($directory);
}

/**
 * @param  array<string, mixed>  $manifest
 */
function beaconTestApplicationDirectory(array $manifest = []): string
{
    $directory = beaconTestTempDirectory();

    $defaultManifest = [
        'name' => 'acme/app',
        'require' => [
            'php' => '^8.3',
        ],
        'scripts' => [
            'test' => '@php artisan test',
        ],
    ];

    file_put_contents(
        $directory.'/composer.json',
        json_encode(array_replace_recursive($defaultManifest, $manifest), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
    );

    return $directory;
}
