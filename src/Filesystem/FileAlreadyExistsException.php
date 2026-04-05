<?php

declare(strict_types=1);

namespace DevOption\Beacon\Filesystem;

use RuntimeException;

final class FileAlreadyExistsException extends RuntimeException
{
    public static function forPath(string $path): self
    {
        return new self(sprintf('Refusing to overwrite existing file [%s].', $path));
    }
}
