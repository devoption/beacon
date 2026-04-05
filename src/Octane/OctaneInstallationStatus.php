<?php

declare(strict_types=1);

namespace DevOption\Beacon\Octane;

enum OctaneInstallationStatus: string
{
    case AlreadyInstalled = 'already-installed';
    case Installed = 'installed';
}
