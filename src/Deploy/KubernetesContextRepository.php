<?php

declare(strict_types=1);

namespace DevOption\Beacon\Deploy;

use Illuminate\Support\Facades\Process;
use RuntimeException;

final class KubernetesContextRepository
{
    public function discover(string $basePath): KubernetesContexts
    {
        $availableContexts = $this->availableContexts($basePath);
        $currentContext = $this->currentContext($basePath);

        if (! in_array($currentContext, $availableContexts, true)) {
            array_unshift($availableContexts, $currentContext);
            $availableContexts = array_values(array_unique($availableContexts));
        }

        return new KubernetesContexts($availableContexts, $currentContext);
    }

    /**
     * @return array<int, string>
     */
    private function availableContexts(string $basePath): array
    {
        $result = Process::path($basePath)->run([
            'kubectl',
            'config',
            'get-contexts',
            '-o',
            'name',
        ]);

        if (! $result->successful()) {
            throw new RuntimeException($this->failureMessage(
                'Unable to discover Kubernetes contexts.',
                $result->errorOutput(),
            ));
        }

        $contexts = array_values(array_filter(array_map(
            static fn (string $context): string => trim($context),
            preg_split('/\R+/', $result->output()) ?: [],
        )));

        if ($contexts === []) {
            throw new RuntimeException('Unable to discover Kubernetes contexts. kubectl returned no configured contexts.');
        }

        return $contexts;
    }

    private function currentContext(string $basePath): string
    {
        $result = Process::path($basePath)->run([
            'kubectl',
            'config',
            'current-context',
        ]);

        if (! $result->successful()) {
            throw new RuntimeException($this->failureMessage(
                'Unable to determine the current Kubernetes context.',
                $result->errorOutput(),
            ));
        }

        $currentContext = trim($result->output());

        if ($currentContext === '') {
            throw new RuntimeException('Unable to determine the current Kubernetes context. kubectl returned an empty value.');
        }

        return $currentContext;
    }

    private function failureMessage(string $prefix, string $errorOutput): string
    {
        $errorOutput = trim($errorOutput);

        return $errorOutput !== '' ? sprintf('%s %s', $prefix, $errorOutput) : $prefix;
    }
}
