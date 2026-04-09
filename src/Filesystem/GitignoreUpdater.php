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
            : (preg_split('/\R/', $contents) ?: []);
        $existingEntries = array_values(array_filter(array_map(
            static fn (string $line): string => trim($line),
            $lines,
        ), static fn (string $line): bool => $line !== ''));
        $missingEntries = [];

        foreach ($entries as $entry) {
            if (! in_array($entry, $existingEntries, true)) {
                $missingEntries[] = $entry;
            }
        }

        if ($missingEntries === []) {
            return $this->writer->write($path, $contents, ExistingFileBehavior::Overwrite);
        }

        $lineEnding = str_contains($contents, "\r\n") ? "\r\n" : "\n";
        $separator = $contents === '' || preg_match('/\R\z/', $contents) === 1 ? '' : $lineEnding;
        $updatedContents = $contents
            .$separator
            .implode($lineEnding, $missingEntries)
            .$lineEnding;

        return $this->writer->write($path, $updatedContents, ExistingFileBehavior::Overwrite);
    }
}
