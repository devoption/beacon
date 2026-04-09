<?php

declare(strict_types=1);

namespace DevOption\Beacon\Install;

use DevOption\Beacon\Composer\ComposerScriptsUpdater;
use DevOption\Beacon\Docker\DockerfileGenerator;
use DevOption\Beacon\Filesystem\ExistingFileBehavior;
use DevOption\Beacon\Filesystem\GitignoreUpdater;
use DevOption\Beacon\Helm\HelmChartGenerator;
use DevOption\Beacon\Octane\OctaneInstaller;

final readonly class InstallWorkflow
{
    public function __construct(
        private OctaneInstaller $octaneInstaller,
        private DockerfileGenerator $dockerfileGenerator,
        private HelmChartGenerator $helmChartGenerator,
        private ComposerScriptsUpdater $composerScriptsUpdater,
        private GitignoreUpdater $gitignoreUpdater,
    ) {
    }

    public function run(string $basePath, InstallConfiguration $configuration): InstallResult
    {
        $octane = $configuration->runtime === 'octane'
            ? $this->octaneInstaller->ensureInstalled($basePath)
            : null;

        $dockerfile = in_array($configuration->deploymentTarget, ['docker', 'docker-and-helm'], true)
            ? $this->dockerfileGenerator->write($basePath, $configuration, ExistingFileBehavior::Overwrite)
            : null;

        $helmChart = in_array($configuration->deploymentTarget, ['helm', 'docker-and-helm'], true)
            ? $this->helmChartGenerator->write($basePath, $configuration, ExistingFileBehavior::Overwrite)
            : null;

        $composerManifest = $configuration->updateComposerScripts
            ? $this->composerScriptsUpdater->write(
                rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'composer.json',
                $configuration,
            )
            : null;

        $gitignore = $configuration->usesHelm()
            ? $this->gitignoreUpdater->ensureEntries(
                rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.gitignore',
                ['/charts/*/values.*.secrets.yaml'],
            )
            : null;

        return new InstallResult($octane, $dockerfile, $helmChart, $composerManifest, $gitignore);
    }
}
