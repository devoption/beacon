<?php

declare(strict_types=1);

namespace DevOption\Beacon\Deploy;

use InvalidArgumentException;

final readonly class KubernetesContexts
{
    /**
     * @param  array<int, string>  $available
     */
    public function __construct(
        public array $available,
        public string $current,
    ) {
        if ($this->available === []) {
            throw new InvalidArgumentException('At least one Kubernetes context must be available.');
        }

        if (! in_array($this->current, $this->available, true)) {
            throw new InvalidArgumentException('The current Kubernetes context must be included in the available contexts.');
        }
    }

    /**
     * @return array<string, string>
     */
    public function promptOptions(): array
    {
        return array_combine($this->available, $this->available) ?: [];
    }
}
