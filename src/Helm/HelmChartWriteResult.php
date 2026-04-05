<?php

declare(strict_types=1);

namespace DevOption\Beacon\Helm;

use DevOption\Beacon\Filesystem\FileWriteResult;

final readonly class HelmChartWriteResult
{
    /**
     * @param  array<string, FileWriteResult>  $files
     */
    public function __construct(
        public string $chartPath,
        public array $files,
    ) {
    }
}
