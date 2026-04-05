<?php

declare(strict_types=1);

use DevOption\Beacon\Filesystem\ExistingFileBehavior;
use DevOption\Beacon\Filesystem\FileAlreadyExistsException;
use DevOption\Beacon\Filesystem\FileWriteStatus;
use DevOption\Beacon\Filesystem\SafeFileWriter;

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

it('creates a file and its parent directories when they do not exist', function (): void {
    $directory = beaconTestTempDirectory();
    $path = $directory.'/deploy/Dockerfile';

    try {
        $result = (new SafeFileWriter)->write($path, 'FROM php:8.4-cli');

        expect($result->status)->toBe(FileWriteStatus::Created)
            ->and($result->created())->toBeTrue()
            ->and($path)->toBeFile()
            ->and(file_get_contents($path))->toBe('FROM php:8.4-cli');
    } finally {
        removeBeaconTestDirectory($directory);
    }
});

it('returns unchanged when the target file already has the same contents', function (): void {
    $directory = beaconTestTempDirectory();
    $path = $directory.'/Dockerfile';
    file_put_contents($path, 'FROM php:8.4-cli');

    try {
        $result = (new SafeFileWriter)->write($path, 'FROM php:8.4-cli');

        expect($result->status)->toBe(FileWriteStatus::Unchanged)
            ->and($result->unchanged())->toBeTrue()
            ->and(file_get_contents($path))->toBe('FROM php:8.4-cli');
    } finally {
        removeBeaconTestDirectory($directory);
    }
});

it('skips writing when the target file exists and skip behavior is requested', function (): void {
    $directory = beaconTestTempDirectory();
    $path = $directory.'/Dockerfile';
    file_put_contents($path, 'FROM php:8.3-cli');

    try {
        $result = (new SafeFileWriter)->write($path, 'FROM php:8.4-cli', ExistingFileBehavior::Skip);

        expect($result->status)->toBe(FileWriteStatus::Skipped)
            ->and($result->skipped())->toBeTrue()
            ->and(file_get_contents($path))->toBe('FROM php:8.3-cli');
    } finally {
        removeBeaconTestDirectory($directory);
    }
});

it('overwrites an existing file when overwrite behavior is requested', function (): void {
    $directory = beaconTestTempDirectory();
    $path = $directory.'/Dockerfile';
    file_put_contents($path, 'FROM php:8.3-cli');

    try {
        $result = (new SafeFileWriter)->write($path, 'FROM php:8.4-cli', ExistingFileBehavior::Overwrite);

        expect($result->status)->toBe(FileWriteStatus::Overwritten)
            ->and($result->overwritten())->toBeTrue()
            ->and(file_get_contents($path))->toBe('FROM php:8.4-cli');
    } finally {
        removeBeaconTestDirectory($directory);
    }
});

it('throws when the target file exists and overwrite is not allowed', function (): void {
    $directory = beaconTestTempDirectory();
    $path = $directory.'/Dockerfile';
    file_put_contents($path, 'FROM php:8.3-cli');

    try {
        expect(fn () => (new SafeFileWriter)->write($path, 'FROM php:8.4-cli'))
            ->toThrow(FileAlreadyExistsException::class);
    } finally {
        removeBeaconTestDirectory($directory);
    }
});
