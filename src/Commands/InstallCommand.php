<?php

declare(strict_types=1);

namespace DevOption\Beacon\Commands;

use DevOption\Beacon\Filesystem\FileWriteResult;
use DevOption\Beacon\Filesystem\FileWriteStatus;
use DevOption\Beacon\Helm\HelmChartWriteResult;
use DevOption\Beacon\Install\InstallConfiguration;
use DevOption\Beacon\Install\InstallConfigurationCollector;
use DevOption\Beacon\Install\InstallResult;
use DevOption\Beacon\Install\InstallWorkflow;
use DevOption\Beacon\Octane\OctaneInstallationResult;
use Illuminate\Console\Command;
use Throwable;

use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;

class InstallCommand extends Command
{
    protected $signature = 'beacon:install';

    protected $description = 'Install Beacon into the current Laravel application';

    public function handle(InstallConfigurationCollector $collector, InstallWorkflow $workflow): int
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
            $result = $workflow->run($this->laravel->basePath(), $configuration);
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
        $this->displayOctaneSummary($result->octane);
        $this->displayArtifactSummary($result);

        outro('Beacon installation completed.');

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
    }

    protected function displayOctaneSummary(?OctaneInstallationResult $octaneInstallation): void
    {
        if ($octaneInstallation === null) {
            return;
        }

        $this->components->info('Octane integration');
        $this->components->twoColumnDetail('Dependency', $octaneInstallation->summary());
    }

    protected function displayArtifactSummary(InstallResult $result): void
    {
        $this->components->info('Generated artifacts');

        if ($result->dockerfile !== null) {
            $this->components->twoColumnDetail('Dockerfile', $this->formatFileWriteResult($result->dockerfile));
        }

        if ($result->helmChart !== null) {
            $this->components->twoColumnDetail('Helm chart', $this->formatHelmChartResult($result->helmChart));
        }

        $this->components->twoColumnDetail(
            'Composer manifest',
            $result->composerManifest !== null
                ? $this->formatFileWriteResult($result->composerManifest)
                : 'Left unchanged'
        );
    }

    protected function formatFileWriteResult(FileWriteResult $result): string
    {
        return match ($result->status) {
            FileWriteStatus::Created => 'Created',
            FileWriteStatus::Overwritten => 'Overwritten',
            FileWriteStatus::Skipped => 'Skipped',
            FileWriteStatus::Unchanged => 'Unchanged',
        };
    }

    protected function formatHelmChartResult(HelmChartWriteResult $result): string
    {
        $statuses = array_map(
            static fn (FileWriteResult $file): FileWriteStatus => $file->status,
            $result->files,
        );

        $uniqueStatuses = array_values(array_unique(array_map(
            static fn (FileWriteStatus $status): string => $status->value,
            $statuses,
        )));

        if (count($uniqueStatuses) === 1) {
            $firstFile = array_values($result->files)[0];

            return sprintf(
                '%s (%d files)',
                $this->formatFileWriteResult($firstFile),
                count($result->files),
            );
        }

        $counts = array_count_values(array_map(
            static fn (FileWriteStatus $status): string => $status->value,
            $statuses,
        ));

        ksort($counts);

        $summary = implode(', ', array_map(
            static fn (string $status, int $count): string => sprintf('%s: %d', $status, $count),
            array_keys($counts),
            $counts,
        ));

        return sprintf('Mixed (%s)', $summary);
    }
}
