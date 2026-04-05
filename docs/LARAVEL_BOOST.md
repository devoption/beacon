# Laravel Boost In Beacon

Beacon ships Laravel Boost support for contributors and downstream Laravel applications that install the package.

## What Beacon provides

Beacon currently includes:

- a package guideline at `resources/boost/guidelines/core.blade.php`
- task-focused package skills under `resources/boost/skills/*`

These files are intended to help AI coding agents work with Beacon using the package's existing architecture and workflow rules.

## Included skills

Beacon currently ships these skills:

- `beacon-install-command-development`
- `beacon-generator-development`
- `beacon-testing-and-matrix`
- `beacon-release-maintenance`

## When to use each skill

- use `beacon-install-command-development` when working on the install command, prompt flow, or Octane-related install orchestration
- use `beacon-generator-development` when working on Dockerfile, Helm, Composer script, or filesystem generation behavior
- use `beacon-testing-and-matrix` when adjusting Pest coverage, Testbench behavior, or CI version matrix support
- use `beacon-release-maintenance` when changing release automation, publish-readiness, or release documentation

## Repository guidance

When updating Beacon's Boost assets:

- keep the guidance package-specific rather than repeating generic Laravel advice
- update the relevant skill when the package workflow for that area changes
- keep skill instructions aligned with `CONTRIBUTING.md`, `README.md`, and `docs/RELEASING.md`
- verify package archive behavior so shipped Boost assets stay available to Boost consumers

## For downstream Laravel apps

Boost's package support is designed so these guidelines and skills can be installed from Beacon when a Laravel application uses both Beacon and Laravel Boost.

Package-shipped guidance is meant to improve:

- command changes
- generator changes
- testing work
- release and publish maintenance
