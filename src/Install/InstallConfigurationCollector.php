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

        $applicationName = $this->askApplicationName($defaults->applicationName);
        $runtime = $this->askRuntime($defaults->runtime);
        $deploymentTarget = $this->askDeploymentTarget($defaults->deploymentTarget);
        $updateComposerScripts = $this->askUpdateComposerScripts($defaults->updateComposerScripts);
        $secretHandling = $this->usesHelm($deploymentTarget)
            ? $this->askSecretHandling($defaults->secretHandling)
            : $defaults->secretHandling;
        $existingSecretName = $this->usesHelm($deploymentTarget) && $secretHandling === 'existing-secret'
            ? $this->askExistingSecretName($defaults->existingSecretName ?? $this->defaultExistingSecretName($applicationName))
            : null;

        return new InstallConfiguration(
            applicationName: $applicationName,
            runtime: $runtime,
            deploymentTarget: $deploymentTarget,
            updateComposerScripts: $updateComposerScripts,
            secretHandling: $secretHandling,
            existingSecretName: $existingSecretName,
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
            secretHandling: 'managed-secret',
            existingSecretName: null,
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

    protected function askSecretHandling(string $default): string
    {
        /** @var string $secretHandling */
        $secretHandling = select(
            label: 'How should Beacon handle sensitive application environment values?',
            options: InstallConfiguration::SECRET_HANDLING_OPTIONS,
            default: $default
        );

        return $secretHandling;
    }

    protected function askExistingSecretName(string $default): string
    {
        return trim(text(
            label: 'What is the existing Kubernetes secret name?',
            default: $default,
            required: 'A Kubernetes secret name is required.',
            validate: fn (string $value): ?string => $this->isValidSecretName(trim($value))
                ? null
                : 'Use a valid Kubernetes secret name (lowercase letters, numbers, and dashes).'
        ));
    }

    protected function normalizeApplicationName(string $applicationName): string
    {
        return trim($applicationName);
    }

    protected function defaultExistingSecretName(string $applicationName): string
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $applicationName) ?? '');
        $slug = trim($slug, '-');
        $slug = $slug !== '' ? $slug : 'beacon';

        return substr($slug.'-env', 0, 253);
    }

    protected function usesHelm(string $deploymentTarget): bool
    {
        return in_array($deploymentTarget, ['helm', 'docker-and-helm'], true);
    }

    private function isValidSecretName(string $value): bool
    {
        return $value !== ''
            && strlen($value) <= 253
            && preg_match('/^[a-z0-9]([-a-z0-9]*[a-z0-9])?$/', $value) === 1;
    }
}
