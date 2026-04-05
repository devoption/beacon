<?php

declare(strict_types=1);

namespace DevOption\Beacon\Octane;

use Illuminate\Support\Facades\Process;
use RuntimeException;

final readonly class OctaneInstaller
{
    public function __construct(
        private string $composerBinary = 'composer',
    ) {
    }

    public function ensureInstalled(string $basePath): OctaneInstallationResult
    {
        if ($this->isInstalled($basePath)) {
            return new OctaneInstallationResult(OctaneInstallationStatus::AlreadyInstalled);
        }

        $result = Process::path($basePath)->run([
            $this->composerBinary,
            'require',
            'laravel/octane',
            '--no-interaction',
            '--no-progress',
        ]);

        if (! $result->successful()) {
            $message = trim($result->errorOutput());

            if ($message === '') {
                $message = trim($result->output());
            }

            if ($message === '') {
                $message = 'The Composer require command failed without output.';
            }

            throw new RuntimeException(sprintf('Unable to install Laravel Octane. %s', $message));
        }

        return new OctaneInstallationResult(OctaneInstallationStatus::Installed);
    }

    public function isInstalled(string $basePath): bool
    {
        $manifest = $this->readComposerManifest($basePath);

        return array_key_exists('laravel/octane', $this->manifestPackages($manifest, 'require'))
            || array_key_exists('laravel/octane', $this->manifestPackages($manifest, 'require-dev'));
    }

    /**
     * @return array<string, mixed>
     */
    private function readComposerManifest(string $basePath): array
    {
        $path = rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'composer.json';
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read composer manifest [%s].', $path));
        }

        try {
            $manifest = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException(
                sprintf('Unable to decode composer manifest [%s].', $path),
                previous: $exception,
            );
        }

        if (! is_array($manifest)) {
            throw new RuntimeException(sprintf('Composer manifest [%s] must decode to an object.', $path));
        }

        return $manifest;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, mixed>
     */
    private function manifestPackages(array $manifest, string $section): array
    {
        $packages = $manifest[$section] ?? [];

        if (! is_array($packages) || ($packages !== [] && array_is_list($packages))) {
            throw new RuntimeException(sprintf('Composer manifest section [%s] must be an object-like map.', $section));
        }

        return $packages;
    }
}
