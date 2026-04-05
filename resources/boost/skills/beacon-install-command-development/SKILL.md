---
name: beacon-install-command-development
description: Work on Beacon's install command, prompt flow, and install orchestration with the package's existing conventions.
---

# Beacon Install Command Development

## When to use this skill

Use this skill when working on:

- `php artisan beacon:install`
- install prompt collection
- install summary output
- install workflow orchestration
- Octane install integration triggered from the command

## Package files

- `src/Commands/InstallCommand.php`
- `src/Install/InstallConfiguration.php`
- `src/Install/InstallConfigurationCollector.php`
- `src/Install/InstallWorkflow.php`
- `src/Install/InstallResult.php`
- `src/Octane/*`

## Repository-only references

These files are available in the source repository, but are not shipped in the Composer dist archive:

- [`tests/Feature/InstallCommandTest.php`](https://github.com/devoption/beacon/blob/main/tests/Feature/InstallCommandTest.php)
- [`tests/Feature/OctaneInstallerTest.php`](https://github.com/devoption/beacon/blob/main/tests/Feature/OctaneInstallerTest.php)

## Guidance

- keep the command thin and push reusable behavior into focused services
- use Laravel Prompts for interactive collection
- preserve rerunnable install behavior and clear status summaries
- prefer feature tests for command-level behavior and integration
- keep package behavior compatible with supported Laravel versions through Testbench

## Common workflow

1. Update the install command or install service class.
2. Add or adjust feature coverage in `tests/Feature/InstallCommandTest.php`.
3. If Octane behavior changes, also update `tests/Feature/OctaneInstallerTest.php`.
4. Run `composer test`.
