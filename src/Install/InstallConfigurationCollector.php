<?php

declare(strict_types=1);

namespace DevOption\Beacon\Install;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class InstallConfigurationCollector
{
    public function collect(
        string $basePath,
        ?string $applicationName = null,
        bool $interactive = true,
    ): InstallConfiguration {
        $defaults = $this->defaultConfiguration($basePath, $applicationName);

        if (! $interactive) {
            return $defaults;
        }

        return new InstallConfiguration(
            applicationName: $this->askApplicationName($defaults->applicationName),
            runtime: $this->askRuntime($defaults->runtime),
            deploymentTarget: $this->askDeploymentTarget($defaults->deploymentTarget),
            updateComposerScripts: $this->askUpdateComposerScripts($defaults->updateComposerScripts),
        );
    }

    public function defaultConfiguration(string $basePath, ?string $applicationName = null): InstallConfiguration
    {
        $normalizedApplicationName = $this->normalizeApplicationName($applicationName ?? '');

        return new InstallConfiguration(
            applicationName: $normalizedApplicationName !== ''
                ? $normalizedApplicationName
                : basename($basePath),
            runtime: 'php-fpm',
            deploymentTarget: 'docker-and-helm',
            updateComposerScripts: true,
        );
    }

    protected function askApplicationName(string $default): string
    {
        return $this->normalizeApplicationName(text(
            label: 'What is the application name?',
            default: $default,
            required: 'An application name is required.',
            validate: fn (string $value): ?string => strlen(trim($value)) >= 2
                ? null
                : 'The application name must be at least 2 characters.'
        ));
    }

    protected function askRuntime(string $default): string
    {
        /** @var string $runtime */
        $runtime = select(
            label: 'Which application runtime should Beacon prepare for?',
            options: InstallConfiguration::RUNTIME_OPTIONS,
            default: $default
        );

        return $runtime;
    }

    protected function askDeploymentTarget(string $default): string
    {
        /** @var string $deploymentTarget */
        $deploymentTarget = select(
            label: 'Which deployment scaffolding should Beacon plan to generate?',
            options: InstallConfiguration::DEPLOYMENT_TARGET_OPTIONS,
            default: $default
        );

        return $deploymentTarget;
    }

    protected function askUpdateComposerScripts(bool $default): bool
    {
        return confirm(
            label: 'Should Beacon plan to update Composer scripts during installation?',
            default: $default
        );
    }

    protected function normalizeApplicationName(string $applicationName): string
    {
        return trim($applicationName);
    }
}
