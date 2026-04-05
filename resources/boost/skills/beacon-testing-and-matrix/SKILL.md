---
name: beacon-testing-and-matrix
description: Work on Beacon's Pest suite, Testbench coverage, and GitHub Actions compatibility matrix across supported Laravel and PHP versions.
---

# Beacon Testing And Matrix

## When to use this skill

Use this skill when working on:

- Pest tests
- Orchestra Testbench setup
- CI matrix maintenance
- Laravel version compatibility updates
- nightly or forward-compatibility workflow adjustments

## Package files

- `composer.json`

## Repository-only references

These files are available in the source repository, but are not shipped in the Composer dist archive:

- [`tests/`](https://github.com/devoption/beacon/tree/main/tests)
- [`phpunit.xml.dist`](https://github.com/devoption/beacon/blob/main/phpunit.xml.dist)
- [`.github/workflows/ci.yml`](https://github.com/devoption/beacon/blob/main/.github/workflows/ci.yml)
- [`.github/workflows/publish-readiness.yml`](https://github.com/devoption/beacon/blob/main/.github/workflows/publish-readiness.yml)

## Guidance

- keep stable lanes strict and forward-compatibility lanes clearly marked
- document practical nightly limitations in workflow comments or docs
- prefer compatibility fixes that keep the test suite version-aware rather than version-fragile
- validate workflow YAML and local package checks when editing CI files

## Common workflow

1. Update tests or workflow configuration.
2. Run the smallest relevant Pest target first.
3. Run `composer test` when the change is complete.
4. If CI files changed, validate the YAML locally.
