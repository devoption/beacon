<laravel-boost-guidelines>
=== beacon rules ===

## Beacon package focus

Beacon is a Laravel package, not a standalone Laravel application.

- work inside package conventions and Orchestra Testbench
- do not scaffold a full Laravel app unless a test fixture clearly requires it
- prefer reusable package classes, stubs, and small focused services over app-style glue code

## Package architecture

When changing Beacon, preserve the current package boundaries:

- `src/Commands` for Artisan command entrypoints
- `src/Install` for install configuration collection and orchestration
- `src/Docker` for Dockerfile generation
- `src/Helm` for Helm chart generation
- `src/Composer` for composer.json script management
- `src/Filesystem` for safe file writing behavior
- `src/Octane` for Laravel Octane dependency handling
- `stubs/` for generated file templates

Prefer extending these areas instead of adding unrelated abstractions.

## Testing expectations

Beacon uses Pest with Orchestra Testbench.

- add or update tests for behavior changes
- prefer focused unit tests for generators and utility services
- use feature tests for command flow and package integration
- keep tests compatible with Beacon's supported Laravel and PHP matrix

## Contributor workflow

Beacon follows an issue-first workflow.

- implement one GitHub issue per branch
- keep branches and commits scoped to that issue only
- use conventional commits
- do not mix unrelated docs, CI, and feature work in one change unless the tracked issue requires it

## Documentation and scope

- keep docs aligned with the current MVP
- do not promise cloud-specific deployment features that Beacon does not implement
- prefer explicit wording about generated Docker, Helm, Composer, and Octane support

</laravel-boost-guidelines>
