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
    ): string {
        $result = Process::path($basePath)->run([
            'helm',
            'upgrade',
            '--install',
            $release,
            $chartPath,
            '--namespace',
            $namespace,
            '--create-namespace',
            '--kube-context',
            $context,
        ]);

        if (! $result->successful()) {
            $errorOutput = trim($result->errorOutput());

            throw new RuntimeException(
                $errorOutput !== '' ? $errorOutput : 'Helm command failed.',
            );
        }

        return trim($result->output());
    }
}
