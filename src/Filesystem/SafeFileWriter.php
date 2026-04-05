<?php

declare(strict_types=1);

namespace DevOption\Beacon\Filesystem;

use RuntimeException;

final class SafeFileWriter
{
    public function write(
        string $path,
        string $contents,
        ExistingFileBehavior $existingFileBehavior = ExistingFileBehavior::Error,
    ): FileWriteResult {
        if (is_dir($path)) {
            throw new RuntimeException(sprintf('Cannot write file [%s] because it is a directory.', $path));
        }

        $directory = dirname($path);

        if ($directory !== '' && $directory !== '.' && ! is_dir($directory)) {
            if (! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                throw new RuntimeException(sprintf('Unable to create directory [%s].', $directory));
            }
        }

        if (is_file($path)) {
            $existingContents = file_get_contents($path);

            if ($existingContents === false) {
                throw new RuntimeException(sprintf('Unable to read existing file [%s].', $path));
            }

            if ($existingContents === $contents) {
                return new FileWriteResult($path, FileWriteStatus::Unchanged);
            }

            return match ($existingFileBehavior) {
                ExistingFileBehavior::Skip => new FileWriteResult($path, FileWriteStatus::Skipped),
                ExistingFileBehavior::Overwrite => $this->persist($path, $contents, FileWriteStatus::Overwritten),
                ExistingFileBehavior::Error => throw FileAlreadyExistsException::forPath($path),
            };
        }

        return $this->persist($path, $contents, FileWriteStatus::Created);
    }

    private function persist(string $path, string $contents, FileWriteStatus $status): FileWriteResult
    {
        $directory = dirname($path);
        $temporaryPath = tempnam($directory === '.' ? sys_get_temp_dir() : $directory, 'beacon-');

        if ($temporaryPath === false) {
            throw new RuntimeException(sprintf('Unable to create a temporary file for [%s].', $path));
        }

        try {
            if (file_put_contents($temporaryPath, $contents, LOCK_EX) === false) {
                throw new RuntimeException(sprintf('Unable to write temporary file for [%s].', $path));
            }

            if (! rename($temporaryPath, $path)) {
                throw new RuntimeException(sprintf('Unable to move temporary file into place for [%s].', $path));
            }
        } catch (\Throwable $throwable) {
            if (is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }

            throw $throwable;
        }

        return new FileWriteResult($path, $status);
    }
}
