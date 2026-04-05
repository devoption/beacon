<?php

declare(strict_types=1);

namespace DevOption\Beacon\Filesystem;

final readonly class FileWriteResult
{
    public function __construct(
        public string $path,
        public FileWriteStatus $status,
    ) {
    }

    public function created(): bool
    {
        return $this->status === FileWriteStatus::Created;
    }

    public function overwritten(): bool
    {
        return $this->status === FileWriteStatus::Overwritten;
    }

    public function skipped(): bool
    {
        return $this->status === FileWriteStatus::Skipped;
    }

    public function unchanged(): bool
    {
        return $this->status === FileWriteStatus::Unchanged;
    }
}
