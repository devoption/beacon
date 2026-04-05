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

    public function __construct(
        public string $applicationName,
        public string $runtime,
        public string $deploymentTarget,
        public bool $updateComposerScripts,
    ) {
        if (! array_key_exists($this->runtime, self::RUNTIME_OPTIONS)) {
            throw new InvalidArgumentException(sprintf('Unsupported runtime [%s].', $this->runtime));
        }

        if (! array_key_exists($this->deploymentTarget, self::DEPLOYMENT_TARGET_OPTIONS)) {
            throw new InvalidArgumentException(sprintf('Unsupported deployment target [%s].', $this->deploymentTarget));
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

    /**
     * @return array{
     *     application_name: string,
     *     runtime: string,
     *     deployment_target: string,
     *     update_composer_scripts: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'application_name' => $this->applicationName,
            'runtime' => $this->runtime,
            'deployment_target' => $this->deploymentTarget,
            'update_composer_scripts' => $this->updateComposerScripts,
        ];
    }
}
