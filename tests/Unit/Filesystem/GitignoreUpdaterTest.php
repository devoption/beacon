<?php

declare(strict_types=1);

use DevOption\Beacon\Filesystem\FileWriteStatus;
use DevOption\Beacon\Filesystem\GitignoreUpdater;
use DevOption\Beacon\Filesystem\SafeFileWriter;

it('appends missing gitignore entries without disturbing existing content', function (): void {
    $directory = beaconTestTempDirectory();
    $path = $directory.'/.gitignore';
    file_put_contents($path, "/vendor\n/build\n");

    $updater = new GitignoreUpdater(new SafeFileWriter);

    try {
        $result = $updater->ensureEntries($path, ['/charts/*/values.*.secrets.yaml']);

        expect($result->status)->toBe(FileWriteStatus::Overwritten)
            ->and(file_get_contents($path))->toBe("/vendor\n/build\n/charts/*/values.*.secrets.yaml\n");
    } finally {
        removeBeaconTestDirectory($directory);
    }
});

it('returns unchanged when all gitignore entries are already present', function (): void {
    $directory = beaconTestTempDirectory();
    $path = $directory.'/.gitignore';
    file_put_contents($path, "/vendor\n/charts/*/values.*.secrets.yaml\n");

    $updater = new GitignoreUpdater(new SafeFileWriter);

    try {
        $result = $updater->ensureEntries($path, ['/charts/*/values.*.secrets.yaml']);

        expect($result->status)->toBe(FileWriteStatus::Unchanged);
    } finally {
        removeBeaconTestDirectory($directory);
    }
});

it('preserves existing line endings and trailing blank lines when appending entries', function (): void {
    $directory = beaconTestTempDirectory();
    $path = $directory.'/.gitignore';
    file_put_contents($path, "/vendor\r\n/build\r\n\r\n");

    $updater = new GitignoreUpdater(new SafeFileWriter);

    try {
        $result = $updater->ensureEntries($path, ['/charts/*/values.*.secrets.yaml']);

        expect($result->status)->toBe(FileWriteStatus::Overwritten)
            ->and(file_get_contents($path))->toBe("/vendor\r\n/build\r\n\r\n/charts/*/values.*.secrets.yaml\r\n");
    } finally {
        removeBeaconTestDirectory($directory);
    }
});
