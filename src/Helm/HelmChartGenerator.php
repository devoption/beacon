<?php

declare(strict_types=1);

namespace DevOption\Beacon\Helm;

use DevOption\Beacon\Filesystem\ExistingFileBehavior;
use DevOption\Beacon\Filesystem\SafeFileWriter;
use DevOption\Beacon\Install\InstallConfiguration;
use RuntimeException;

final readonly class HelmChartGenerator
{
    /**
     * @var array<string, string>
     */
    private const STUBS = [
        'Chart.yaml' => 'Chart.yaml.stub',
        'values.yaml' => 'values.yaml.stub',
        'values.local.yaml' => 'values.local.yaml.stub',
        'values.staging.yaml' => 'values.staging.yaml.stub',
        'values.production.yaml' => 'values.production.yaml.stub',
        'templates/_helpers.tpl' => 'templates/_helpers.tpl.stub',
        'templates/deployment.yaml' => 'templates/deployment.yaml.stub',
        'templates/service.yaml' => 'templates/service.yaml.stub',
        'templates/ingress.yaml' => 'templates/ingress.yaml.stub',
    ];

    public function __construct(
        private SafeFileWriter $writer,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function renderFiles(InstallConfiguration $configuration): array
    {
        $replacements = [
            '{{application_name}}' => $this->escapeYamlDoubleQuotedString($configuration->applicationName),
            '{{chart_name}}' => $this->chartName($configuration),
            '{{runtime}}' => $configuration->runtime,
            '{{service_port}}' => (string) $this->servicePort($configuration),
        ];

        $files = [];

        foreach (self::STUBS as $relativePath => $stub) {
            $files[$relativePath] = strtr($this->stubContents($stub), $replacements);
        }

        return $files;
    }

    public function write(
        string $basePath,
        InstallConfiguration $configuration,
        ExistingFileBehavior $existingFileBehavior = ExistingFileBehavior::Error,
    ): HelmChartWriteResult {
        $chartPath = $this->chartPath($basePath, $configuration);
        $results = [];

        foreach ($this->renderFiles($configuration) as $relativePath => $contents) {
            $results[$relativePath] = $this->writer->write(
                $chartPath.DIRECTORY_SEPARATOR.$relativePath,
                $contents,
                $existingFileBehavior,
            );
        }

        return new HelmChartWriteResult($chartPath, $results);
    }

    public function chartPath(string $basePath, InstallConfiguration $configuration): string
    {
        return rtrim($basePath, DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR.'charts'
            .DIRECTORY_SEPARATOR.$this->chartName($configuration);
    }

    private function chartName(InstallConfiguration $configuration): string
    {
        $normalized = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $configuration->applicationName) ?? '');
        $normalized = trim($normalized, '-');
        $normalized = substr($normalized, 0, 63);
        $normalized = trim($normalized, '-');

        return $normalized !== '' ? $normalized : 'beacon';
    }

    private function servicePort(InstallConfiguration $configuration): int
    {
        return match ($configuration->runtime) {
            'php-fpm' => 9000,
            'octane' => 8000,
            default => throw new RuntimeException(sprintf(
                'No Helm service port is defined for runtime [%s].',
                $configuration->runtime,
            )),
        };
    }

    private function stubContents(string $stub): string
    {
        $path = dirname(__DIR__, 2).'/stubs/helm/'.$stub;
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read Helm chart stub [%s].', $path));
        }

        return $contents;
    }

    private function escapeYamlDoubleQuotedString(string $value): string
    {
        return strtr($value, [
            '\\' => '\\\\',
            '"' => '\\"',
            "\r" => '\\r',
            "\n" => '\\n',
        ]);
    }
}
