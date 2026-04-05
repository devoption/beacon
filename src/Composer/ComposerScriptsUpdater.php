<?php

declare(strict_types=1);

namespace DevOption\Beacon\Composer;

use DevOption\Beacon\Filesystem\ExistingFileBehavior;
use DevOption\Beacon\Filesystem\FileWriteResult;
use DevOption\Beacon\Filesystem\SafeFileWriter;
use DevOption\Beacon\Install\InstallConfiguration;
use RuntimeException;

final readonly class ComposerScriptsUpdater
{
    /**
     * @var array<string, string>
     */
    private const MANAGED_SCRIPTS = [
        'beacon:build' => 'Build the Beacon production Docker image.',
        'beacon:deploy' => 'Deploy the Beacon Helm release.',
    ];

    public function __construct(
        private SafeFileWriter $writer,
    ) {
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, mixed>
     */
    public function updateManifest(array $manifest, InstallConfiguration $configuration): array
    {
        $manifest['scripts'] = $this->mergeManagedEntries(
            $this->manifestSection($manifest, 'scripts'),
            $this->desiredScripts($configuration),
        );

        $scriptDescriptions = $this->mergeManagedEntries(
            $this->manifestSection($manifest, 'scripts-descriptions'),
            $this->desiredScriptDescriptions($configuration),
        );

        if ($scriptDescriptions === []) {
            unset($manifest['scripts-descriptions']);
        } else {
            $manifest['scripts-descriptions'] = $scriptDescriptions;
        }

        return $manifest;
    }

    public function write(string $composerJsonPath, InstallConfiguration $configuration): FileWriteResult
    {
        $manifest = $this->readManifest($composerJsonPath);

        return $this->writer->write(
            $composerJsonPath,
            $this->encodeManifest($this->updateManifest($manifest, $configuration)),
            ExistingFileBehavior::Overwrite,
        );
    }

    /**
     * @return array<string, string>
     */
    private function desiredScripts(InstallConfiguration $configuration): array
    {
        $slug = $this->applicationSlug($configuration);
        $scripts = [
            'beacon:build' => sprintf(
                'docker build --file Dockerfile --tag %s:latest .',
                $slug
            ),
        ];

        if ($configuration->deploymentTarget !== 'docker') {
            $scripts['beacon:deploy'] = sprintf(
                'helm upgrade --install %1$s ./charts/%1$s --namespace default --create-namespace',
                $slug
            );
        }

        return $scripts;
    }

    /**
     * @return array<string, string>
     */
    private function desiredScriptDescriptions(InstallConfiguration $configuration): array
    {
        $descriptions = [
            'beacon:build' => self::MANAGED_SCRIPTS['beacon:build'],
        ];

        if ($configuration->deploymentTarget !== 'docker') {
            $descriptions['beacon:deploy'] = self::MANAGED_SCRIPTS['beacon:deploy'];
        }

        return $descriptions;
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, string>  $desired
     * @return array<string, mixed>
     */
    private function mergeManagedEntries(array $current, array $desired): array
    {
        foreach (array_keys(self::MANAGED_SCRIPTS) as $managedKey) {
            unset($current[$managedKey]);
        }

        foreach ($desired as $key => $value) {
            $current[$key] = $value;
        }

        return $current;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, mixed>
     */
    private function manifestSection(array $manifest, string $key): array
    {
        $section = $manifest[$key] ?? [];

        if (! is_array($section) || ($section !== [] && array_is_list($section))) {
            throw new RuntimeException(sprintf('Composer manifest section [%s] must be an object-like map.', $key));
        }

        return $section;
    }

    /**
     * @return array<string, mixed>
     */
    private function readManifest(string $composerJsonPath): array
    {
        $contents = file_get_contents($composerJsonPath);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read composer manifest [%s].', $composerJsonPath));
        }

        try {
            $manifest = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException(
                sprintf('Unable to decode composer manifest [%s].', $composerJsonPath),
                previous: $exception,
            );
        }

        if (! is_array($manifest)) {
            throw new RuntimeException(sprintf('Composer manifest [%s] must decode to an object.', $composerJsonPath));
        }

        return $manifest;
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function encodeManifest(array $manifest): string
    {
        try {
            $encoded = json_encode(
                $manifest,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $exception) {
            throw new RuntimeException('Unable to encode the updated composer manifest.', previous: $exception);
        }

        return $encoded.PHP_EOL;
    }

    private function applicationSlug(InstallConfiguration $configuration): string
    {
        $normalized = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $configuration->applicationName) ?? '');
        $normalized = trim($normalized, '-');
        $normalized = substr($normalized, 0, 63);
        $normalized = trim($normalized, '-');

        return $normalized !== '' ? $normalized : 'beacon';
    }
}
