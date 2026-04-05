<?php

declare(strict_types=1);

namespace DevOption\Beacon\Commands;

use DevOption\Beacon\Install\InstallConfiguration;
use DevOption\Beacon\Install\InstallConfigurationCollector;
use DevOption\Beacon\Octane\OctaneInstallationResult;
use DevOption\Beacon\Octane\OctaneInstaller;
use Illuminate\Console\Command;
use Throwable;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;

class InstallCommand extends Command
{
    protected $signature = 'beacon:install';

    protected $description = 'Install Beacon into the current Laravel application';

    public function handle(InstallConfigurationCollector $collector, OctaneInstaller $octaneInstaller): int
    {
        intro('Beacon will guide you through the initial production install setup.');

        $configuredApplicationName = $this->laravel->config->get('app.name');
        $applicationName = is_string($configuredApplicationName) ? $configuredApplicationName : null;

        $configuration = $collector->collect(
            basePath: $this->laravel->basePath(),
            applicationName: $applicationName,
            interactive: $this->input->isInteractive(),
        );

        try {
            $octaneInstallation = $this->ensureOctaneIsAvailable($configuration, $octaneInstaller);
        } catch (Throwable $throwable) {
            $message = trim($throwable->getMessage());

            $this->components->error(
                $message !== ''
                    ? sprintf('Failed to ensure Octane is available: %s', $message)
                    : 'Failed to ensure Octane is available.'
            );

            return self::FAILURE;
        }

        $this->displayConfigurationSummary($configuration);
        $this->displayOctaneSummary($octaneInstallation);

        outro('Beacon collected your installation preferences. File generation will be added in follow-up issues.');

        return self::SUCCESS;
    }

    protected function displayConfigurationSummary(InstallConfiguration $configuration): void
    {
        $this->components->info('Install skeleton summary');
        $this->components->twoColumnDetail('Application', $configuration->applicationName);
        $this->components->twoColumnDetail('Runtime', $configuration->runtimeLabel());
        $this->components->twoColumnDetail('Scaffolding', $configuration->deploymentTargetLabel());
        $this->components->twoColumnDetail(
            'Composer scripts',
            $configuration->updateComposerScripts ? 'Plan to update' : 'Leave unchanged'
        );
        $this->components->info('No files were generated in this step.');
    }

    protected function ensureOctaneIsAvailable(
        InstallConfiguration $configuration,
        OctaneInstaller $octaneInstaller,
    ): ?OctaneInstallationResult {
        if ($configuration->runtime !== 'octane') {
            return null;
        }

        return $octaneInstaller->ensureInstalled($this->laravel->basePath());
    }

    protected function displayOctaneSummary(?OctaneInstallationResult $octaneInstallation): void
    {
        if ($octaneInstallation === null) {
            return;
        }

        $this->components->info('Octane integration');
        $this->components->twoColumnDetail('Dependency', $octaneInstallation->summary());
    }
}
