<?php

declare(strict_types=1);

namespace DevOption\Beacon\Install;

use InvalidArgumentException;

final readonly class IngressProviderRecommendation
{
    public function __construct(
        public string $provider,
        public string $clusterContext,
    ) {
        if (! array_key_exists($this->provider, InstallConfiguration::INGRESS_PROVIDER_OPTIONS)) {
            throw new InvalidArgumentException(sprintf('Unsupported ingress provider recommendation [%s].', $this->provider));
        }

        if (trim($this->clusterContext) === '') {
            throw new InvalidArgumentException('A cluster context is required for ingress recommendations.');
        }
    }
}
