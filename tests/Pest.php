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
