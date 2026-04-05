<?php

declare(strict_types=1);

namespace DevOption\Beacon\Install;

use DevOption\Beacon\Filesystem\FileWriteResult;
use DevOption\Beacon\Helm\HelmChartWriteResult;
use DevOption\Beacon\Octane\OctaneInstallationResult;

final readonly class InstallResult
{
    public function __construct(
        public ?OctaneInstallationResult $octane,
        public ?FileWriteResult $dockerfile,
        public ?HelmChartWriteResult $helmChart,
        public ?FileWriteResult $composerManifest,
    ) {
    }
}
