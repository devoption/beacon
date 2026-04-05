<?php

declare(strict_types=1);

namespace DevOption\Beacon\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class InstallCommand extends Command
{
    /**
     * @var array<string, string>
     */
    protected const RUNTIME_OPTIONS = [
        'php-fpm' => 'PHP-FPM',
        'octane' => 'Laravel Octane',
    ];

    /**
     * @var array<string, string>
     */
    protected const DEPLOYMENT_TARGET_OPTIONS = [
        'docker' => 'Dockerfile',
        'helm' => 'Helm chart',
        'docker-and-helm' => 'Dockerfile and Helm chart',
    ];

    protected $signature = 'beacon:install';

    protected $description = 'Install Beacon into the current Laravel application';

    public function handle(): int
    {
        intro('Beacon will guide you through the initial production install setup.');

        $configuration = $this->collectSkeletonConfiguration();

        $this->displayConfigurationSummary($configuration);

        outro('Beacon collected your installation preferences. File generation will be added in follow-up issues.');

        return self::SUCCESS;
    }

    /**
     * @return array{
     *     application_name: string,
     *     runtime: string,
     *     deployment_target: string,
     *     update_composer_scripts: bool
     * }
     */
    protected function collectSkeletonConfiguration(): array
    {
        $defaults = [
            'application_name' => $this->laravel->config->get('app.name', basename($this->laravel->basePath())),
            'runtime' => 'php-fpm',
            'deployment_target' => 'docker-and-helm',
            'update_composer_scripts' => true,
        ];

        if (! $this->input->isInteractive()) {
            return $defaults;
        }

        return [
            'application_name' => trim(text(
                label: 'What is the application name?',
                default: $defaults['application_name'],
                required: 'An application name is required.',
                validate: fn (string $value): ?string => mb_strlen(trim($value)) >= 2
                    ? null
                    : 'The application name must be at least 2 characters.'
            )),
            'runtime' => select(
                label: 'Which application runtime should Beacon prepare for?',
                options: self::RUNTIME_OPTIONS,
                default: $defaults['runtime']
            ),
            'deployment_target' => select(
                label: 'Which deployment scaffolding should Beacon plan to generate?',
                options: self::DEPLOYMENT_TARGET_OPTIONS,
                default: $defaults['deployment_target']
            ),
            'update_composer_scripts' => confirm(
                label: 'Should Beacon plan to update Composer scripts during installation?',
                default: $defaults['update_composer_scripts']
            ),
        ];
    }

    /**
     * @param  array{
     *     application_name: string,
     *     runtime: string,
     *     deployment_target: string,
     *     update_composer_scripts: bool
     * }  $configuration
     */
    protected function displayConfigurationSummary(array $configuration): void
    {
        $this->components->info('Install skeleton summary');
        $this->components->twoColumnDetail('Application', (string) Arr::get($configuration, 'application_name'));
        $this->components->twoColumnDetail('Runtime', (string) Arr::get(self::RUNTIME_OPTIONS, Arr::get($configuration, 'runtime'), 'Unknown'));
        $this->components->twoColumnDetail('Scaffolding', (string) Arr::get(self::DEPLOYMENT_TARGET_OPTIONS, Arr::get($configuration, 'deployment_target'), 'Unknown'));
        $this->components->twoColumnDetail(
            'Composer scripts',
            Arr::get($configuration, 'update_composer_scripts') ? 'Plan to update' : 'Leave unchanged'
        );
        $this->components->info('No files were generated in this step.');
    }
}
