<?php

declare(strict_types=1);

namespace DevOption\Beacon\Filesystem;

use RuntimeException;

final readonly class GitignoreUpdater
{
    public function __construct(
        private SafeFileWriter $writer,
    ) {
    }

    /**
     * @param  array<int, string>  $entries
     */
    public function ensureEntries(string $path, array $entries): FileWriteResult
    {
        $entries = array_values(array_filter(array_map(
            static fn (string $entry): string => trim($entry),
            $entries,
        ), static fn (string $entry): bool => $entry !== ''));

        $entries = array_values(array_unique($entries));

        if ($entries === []) {
            throw new RuntimeException('At least one .gitignore entry is required.');
        }

        $contents = '';

        if (is_file($path)) {
            $contents = file_get_contents($path);

            if ($contents === false) {
                throw new RuntimeException(sprintf('Unable to read .gitignore [%s].', $path));
            }
        }

        $lines = $contents === ''
            ? []
            : (preg_split('/\R/', rtrim($contents, "\r\n")) ?: []);
        $existingEntries = array_map('trim', $lines);

        foreach ($entries as $entry) {
            if (! in_array($entry, $existingEntries, true)) {
                $lines[] = $entry;
            }
        }

        $updatedContents = implode(PHP_EOL, $lines).PHP_EOL;

        return $this->writer->write($path, $updatedContents, ExistingFileBehavior::Overwrite);
    }
}
