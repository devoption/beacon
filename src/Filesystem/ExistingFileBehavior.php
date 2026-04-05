<?php

declare(strict_types=1);

namespace DevOption\Beacon\Filesystem;

enum ExistingFileBehavior: string
{
    case Error = 'error';
    case Overwrite = 'overwrite';
    case Skip = 'skip';
}
