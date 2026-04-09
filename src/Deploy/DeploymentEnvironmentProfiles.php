<?php

declare(strict_types=1);

namespace DevOption\Beacon\Deploy;

use RuntimeException;

final readonly class DeploymentEnvironmentProfiles
{
    /**
     * @param  array<int, string>  $names
     */
    public function __construct(
        public array $names,
    ) {
        if ($this->names === []) {
            throw new RuntimeException('At least one deployment environment profile must be available.');
        }
    }

    /**
     * @return array<int, string>
     */
    public static function defaults(): array
    {
        return ['local', 'staging', 'production'];
    }

    /**
     * @return array<string, string>
     */
    public function promptOptions(): array
    {
        return array_combine($this->names, array_map('ucfirst', $this->names)) ?: [];
    }

    public function default(): string
    {
        return in_array('local', $this->names, true) ? 'local' : $this->names[0];
    }

    public function overlayRelativePath(string $environment): string
    {
        $this->guardEnvironment($environment);

        return sprintf('values.%s.yaml', $environment);
    }

    public function overlayAbsolutePath(string $chartAbsolutePath, string $environment): string
    {
        return rtrim($chartAbsolutePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$this->overlayRelativePath($environment);
    }

    private function guardEnvironment(string $environment): void
    {
        if (! in_array($environment, $this->names, true)) {
            throw new RuntimeException(sprintf(
                'The selected deployment environment [%s] is not available.',
                $environment,
            ));
        }
    }
}
