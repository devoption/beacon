<?php

declare(strict_types=1);

namespace DevOption\Beacon\Install;

use InvalidArgumentException;

final readonly class InstallConfiguration
{
    /**
     * @var array<string, string>
     */
    public const RUNTIME_OPTIONS = [
        'php-fpm' => 'PHP-FPM',
        'octane' => 'Laravel Octane',
    ];

    /**
     * @var array<string, string>
     */
    public const DEPLOYMENT_TARGET_OPTIONS = [
        'docker' => 'Dockerfile',
        'helm' => 'Helm chart',
        'docker-and-helm' => 'Dockerfile and Helm chart',
    ];

    /**
     * @var array<string, string>
     */
    public const SECRET_HANDLING_OPTIONS = [
        'managed-secret' => 'Beacon-managed Helm secret',
        'existing-secret' => 'Existing Kubernetes secret',
    ];

    public function __construct(
        public string $applicationName,
        public string $runtime,
        public string $deploymentTarget,
        public bool $updateComposerScripts,
        public string $secretHandling = 'managed-secret',
        public ?string $existingSecretName = null,
    ) {
        if (! array_key_exists($this->runtime, self::RUNTIME_OPTIONS)) {
            throw new InvalidArgumentException(sprintf('Unsupported runtime [%s].', $this->runtime));
        }

        if (! array_key_exists($this->deploymentTarget, self::DEPLOYMENT_TARGET_OPTIONS)) {
            throw new InvalidArgumentException(sprintf('Unsupported deployment target [%s].', $this->deploymentTarget));
        }

        if (! array_key_exists($this->secretHandling, self::SECRET_HANDLING_OPTIONS)) {
            throw new InvalidArgumentException(sprintf('Unsupported secret handling mode [%s].', $this->secretHandling));
        }

        if ($this->secretHandling === 'existing-secret' && ($this->existingSecretName === null || trim($this->existingSecretName) === '')) {
            throw new InvalidArgumentException('An existing secret name is required when using the existing Kubernetes secret mode.');
        }

        if ($this->secretHandling !== 'existing-secret' && $this->existingSecretName !== null) {
            throw new InvalidArgumentException('An existing secret name may only be provided when using the existing Kubernetes secret mode.');
        }
    }

    public function runtimeLabel(): string
    {
        return self::RUNTIME_OPTIONS[$this->runtime];
    }

    public function deploymentTargetLabel(): string
    {
        return self::DEPLOYMENT_TARGET_OPTIONS[$this->deploymentTarget];
    }

    public function secretHandlingLabel(): string
    {
        return self::SECRET_HANDLING_OPTIONS[$this->secretHandling];
    }

    public function usesHelm(): bool
    {
        return in_array($this->deploymentTarget, ['helm', 'docker-and-helm'], true);
    }

    /**
     * @return array{
     *     application_name: string,
     *     runtime: string,
     *     deployment_target: string,
     *     update_composer_scripts: bool,
     *     secret_handling: string,
     *     existing_secret_name: ?string
     * }
     */
    public function toArray(): array
    {
        return [
            'application_name' => $this->applicationName,
            'runtime' => $this->runtime,
            'deployment_target' => $this->deploymentTarget,
            'update_composer_scripts' => $this->updateComposerScripts,
            'secret_handling' => $this->secretHandling,
            'existing_secret_name' => $this->existingSecretName,
        ];
    }
}
