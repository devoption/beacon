---
name: beacon-generator-development
description: Update Beacon's Dockerfile, Helm, Composer script, and filesystem generation layers using the package's stub-driven architecture.
---

# Beacon Generator Development

## When to use this skill

Use this skill when working on:

- Dockerfile generation
- Helm chart scaffolding
- Composer script updates
- file-writing behavior for generated artifacts
- stub template changes

## Package files

- `src/Docker/*`
- `src/Helm/*`
- `src/Composer/*`
- `src/Filesystem/*`
- `stubs/docker/*`
- `stubs/helm/**`

## Repository-only references

These files are available in the source repository, but are not shipped in the Composer dist archive:

- [`tests/Unit/Docker/DockerfileGeneratorTest.php`](https://github.com/devoption/beacon/blob/main/tests/Unit/Docker/DockerfileGeneratorTest.php)
- [`tests/Unit/Helm/HelmChartGeneratorTest.php`](https://github.com/devoption/beacon/blob/main/tests/Unit/Helm/HelmChartGeneratorTest.php)
- [`tests/Unit/Composer/ComposerScriptsUpdaterTest.php`](https://github.com/devoption/beacon/blob/main/tests/Unit/Composer/ComposerScriptsUpdaterTest.php)
- [`tests/Unit/Filesystem/SafeFileWriterTest.php`](https://github.com/devoption/beacon/blob/main/tests/Unit/Filesystem/SafeFileWriterTest.php)

## Guidance

- keep generators stub-driven rather than hardcoding large strings in PHP
- route file writes through `SafeFileWriter`
- preserve idempotent output where possible
- keep generated names and labels slug-safe and deployment-friendly
- favor focused unit tests for rendering and write behavior

## Common workflow

1. Update the generator or stub.
2. Add or adjust unit coverage for the changed generator.
3. If artifact behavior affects install flow, verify `tests/Feature/InstallCommandTest.php`.
4. Run `composer test`.
