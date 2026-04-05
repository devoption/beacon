<?php

declare(strict_types=1);

namespace DevOption\Beacon\Filesystem;

enum FileWriteStatus: string
{
    case Created = 'created';
    case Overwritten = 'overwritten';
    case Skipped = 'skipped';
    case Unchanged = 'unchanged';
}
