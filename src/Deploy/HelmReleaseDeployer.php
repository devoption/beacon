<?php

declare(strict_types=1);

namespace DevOption\Beacon\Deploy;

use Illuminate\Support\Facades\Process;
use RuntimeException;

final class HelmReleaseDeployer
{
    public function deploy(
        string $basePath,
        string $release,
        string $chartPath,
        string $namespace,
        string $context,
        string $sharedValuesPath,
        string $environmentValuesPath,
        ?string $secretValuesPath = null,
    ): string {
        $command = [
            'helm',
            'upgrade',
            '--install',
            $release,
            $chartPath,
            '-f',
            $sharedValuesPath,
            '-f',
            $environmentValuesPath,
        ];

        if ($secretValuesPath !== null) {
            $command[] = '-f';
            $command[] = $secretValuesPath;
        }

        $command = [
            ...$command,
            '--namespace',
            $namespace,
            '--create-namespace',
            '--kube-context',
            $context,
        ];

        $result = Process::path($basePath)->run($command);

        if (! $result->successful()) {
            $errorOutput = trim($result->errorOutput());

            throw new RuntimeException(
                $errorOutput !== '' ? $errorOutput : 'Helm command failed.',
            );
        }

        return trim($result->output());
    }
}
