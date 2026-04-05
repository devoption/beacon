<?php

declare(strict_types=1);

namespace DevOption\Beacon\Docker;

use DevOption\Beacon\Filesystem\ExistingFileBehavior;
use DevOption\Beacon\Filesystem\FileWriteResult;
use DevOption\Beacon\Filesystem\SafeFileWriter;
use DevOption\Beacon\Install\InstallConfiguration;
use RuntimeException;

final readonly class DockerfileGenerator
{
    public function __construct(
        private SafeFileWriter $writer,
    ) {
    }

    public function render(InstallConfiguration $configuration): string
    {
        return str_replace(
            '{{application_name}}',
            $configuration->applicationName,
            $this->stubContentsFor($configuration),
        );
    }

    public function write(
        string $basePath,
        InstallConfiguration $configuration,
        ExistingFileBehavior $existingFileBehavior = ExistingFileBehavior::Error,
    ): FileWriteResult {
        return $this->writer->write(
            $this->dockerfilePath($basePath),
            $this->render($configuration),
            $existingFileBehavior,
        );
    }

    public function dockerfilePath(string $basePath): string
    {
        return rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'Dockerfile';
    }

    private function stubContentsFor(InstallConfiguration $configuration): string
    {
        $stubPath = match ($configuration->runtime) {
            'php-fpm' => dirname(__DIR__, 2).'/stubs/docker/php-fpm.Dockerfile.stub',
            'octane' => dirname(__DIR__, 2).'/stubs/docker/octane.Dockerfile.stub',
            default => throw new RuntimeException(sprintf(
                'No Dockerfile stub is defined for runtime [%s].',
                $configuration->runtime,
            )),
        };

        $contents = file_get_contents($stubPath);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read Dockerfile stub [%s].', $stubPath));
        }

        return $contents;
    }
}
