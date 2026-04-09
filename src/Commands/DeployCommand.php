<?php

declare(strict_types=1);

namespace DevOption\Beacon\Commands;

use DevOption\Beacon\Deploy\HelmReleaseDeployer;
use DevOption\Beacon\Deploy\KubernetesContextRepository;
use DevOption\Beacon\Deploy\KubernetesContexts;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class DeployCommand extends Command
{
    protected $signature = 'beacon:deploy
        {--context= : Kubernetes context to deploy to}
        {--namespace= : Kubernetes namespace to deploy into}
        {--release= : Helm release name override}
        {--chart= : Helm chart path override}';

    protected $description = 'Deploy the Beacon Helm release';

    public function handle(
        KubernetesContextRepository $contextRepository,
        HelmReleaseDeployer $helmReleaseDeployer,
    ): int {
        intro('Beacon will help you choose a Kubernetes deployment target.');

        try {
            $basePath = $this->laravel->basePath();
            $chartPath = $this->chartPath($basePath);
            $release = $this->releaseName($chartPath);
            $contexts = $contextRepository->discover($basePath);
            $context = $this->deploymentContext($contexts);
            $namespace = $this->namespace();

            $this->displayDeploymentSummary($release, $chartPath, $context, $namespace);

            if ($this->input->isInteractive() && ! confirm(
                label: 'Continue with this deployment target?',
                default: true,
            )) {
                $this->components->warn('Beacon deployment cancelled.');

                return self::INVALID;
            }

            $output = $helmReleaseDeployer->deploy(
                basePath: $basePath,
                release: $release,
                chartPath: $chartPath,
                namespace: $namespace,
                context: $context,
            );
        } catch (Throwable $throwable) {
            $message = trim($throwable->getMessage());

            $this->components->error(
                $message !== ''
                    ? sprintf('Beacon deployment failed: %s', $message)
                    : 'Beacon deployment failed.'
            );

            return self::FAILURE;
        }

        if ($output !== '') {
            $this->components->info('Helm output');
            $this->line($output);
        }

        outro('Beacon deployment completed.');

        return self::SUCCESS;
    }

    private function chartPath(string $basePath): string
    {
        $configuredPath = $this->option('chart');

        if (is_string($configuredPath) && trim($configuredPath) !== '') {
            return trim($configuredPath);
        }

        $chartsDirectory = rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'charts';

        if (! is_dir($chartsDirectory)) {
            throw new RuntimeException('Unable to locate a Helm chart. Run Beacon install first or pass --chart=.');
        }

        $configuredApplicationName = $this->laravel->config->get('app.name');
        $applicationName = is_string($configuredApplicationName) ? $configuredApplicationName : '';
        $slug = $this->applicationSlug($applicationName);

        if ($slug !== '' && is_dir($chartsDirectory.DIRECTORY_SEPARATOR.$slug)) {
            return './charts/'.$slug;
        }

        $directories = array_values(array_filter(glob($chartsDirectory.DIRECTORY_SEPARATOR.'*') ?: [], 'is_dir'));

        if (count($directories) === 1) {
            return './charts/'.basename($directories[0]);
        }

        throw new RuntimeException('Unable to determine which Helm chart to deploy. Pass --chart= to choose one explicitly.');
    }

    private function releaseName(string $chartPath): string
    {
        $configuredRelease = $this->option('release');

        if (is_string($configuredRelease) && trim($configuredRelease) !== '') {
            return trim($configuredRelease);
        }

        $release = trim(basename($chartPath));

        if ($release === '' || $release === '.' || $release === DIRECTORY_SEPARATOR) {
            throw new RuntimeException('Unable to determine the Helm release name. Pass --release= to set one explicitly.');
        }

        return $release;
    }

    private function deploymentContext(KubernetesContexts $contexts): string
    {
        $configuredContext = $this->option('context');

        if (is_string($configuredContext) && trim($configuredContext) !== '') {
            $configuredContext = trim($configuredContext);

            if (! in_array($configuredContext, $contexts->available, true)) {
                throw new RuntimeException(sprintf(
                    'The selected Kubernetes context [%s] is not available.',
                    $configuredContext,
                ));
            }

            return $configuredContext;
        }

        if (! $this->input->isInteractive()) {
            return $contexts->current;
        }

        /** @var string $selectedContext */
        $selectedContext = select(
            label: 'Which Kubernetes context should Beacon deploy to?',
            options: $contexts->promptOptions(),
            default: $contexts->current,
        );

        return $selectedContext;
    }

    private function namespace(): string
    {
        $configuredNamespace = $this->option('namespace');

        if (is_string($configuredNamespace) && trim($configuredNamespace) !== '') {
            return trim($configuredNamespace);
        }

        if (! $this->input->isInteractive()) {
            return 'default';
        }

        return trim(text(
            label: 'Which namespace should Beacon deploy into?',
            default: 'default',
            validate: static fn (string $value): ?string => trim($value) === '' ? 'A namespace is required.' : null,
        ));
    }

    private function displayDeploymentSummary(
        string $release,
        string $chartPath,
        string $context,
        string $namespace,
    ): void {
        $this->components->info('Deployment target');
        $this->components->twoColumnDetail('Release', $release);
        $this->components->twoColumnDetail('Chart', $chartPath);
        $this->components->twoColumnDetail('Context', $context);
        $this->components->twoColumnDetail('Namespace', $namespace);
    }

    private function applicationSlug(string $applicationName): string
    {
        $normalized = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $applicationName) ?? '');
        $normalized = trim($normalized, '-');
        $normalized = substr($normalized, 0, 63);
        $normalized = trim($normalized, '-');

        return $normalized !== '' ? $normalized : 'beacon';
    }
}
