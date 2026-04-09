<?php

declare(strict_types=1);

namespace DevOption\Beacon\Install;

use Illuminate\Support\Facades\Process;

final class IngressProviderRecommendationRepository
{
    public function recommend(string $basePath): ?IngressProviderRecommendation
    {
        $context = $this->currentContext($basePath);

        if ($context === null) {
            return null;
        }

        $normalized = strtolower($context);

        return match (true) {
            str_contains($normalized, 'rancher-desktop'),
            str_contains($normalized, 'k3s'),
            str_contains($normalized, 'traefik') => new IngressProviderRecommendation('traefik', $context),
            str_contains($normalized, 'nginx'),
            str_contains($normalized, 'ingress-nginx'),
            str_contains($normalized, 'minikube') => new IngressProviderRecommendation('nginx', $context),
            default => null,
        };
    }

    private function currentContext(string $basePath): ?string
    {
        $result = Process::path($basePath)->run([
            'kubectl',
            'config',
            'current-context',
        ]);

        if (! $result->successful()) {
            return null;
        }

        $context = trim($result->output());

        return $context !== '' ? $context : null;
    }
}
