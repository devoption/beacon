---
name: beacon-release-maintenance
description: Maintain Beacon's semantic release, publish-readiness, and package metadata workflows without expanding package runtime scope.
---

# Beacon Release Maintenance

## When to use this skill

Use this skill when working on:

- semantic release automation
- publish-readiness checks
- package metadata and archive exclusions
- release and Packagist maintainer documentation

## Package files

- `composer.json`
- `.gitattributes`

## Repository-only references

These files are available in the source repository, but are not shipped in the Composer dist archive:

- [`.github/workflows/release.yml`](https://github.com/devoption/beacon/blob/main/.github/workflows/release.yml)
- [`.github/workflows/publish-readiness.yml`](https://github.com/devoption/beacon/blob/main/.github/workflows/publish-readiness.yml)
- [`.releaserc.json`](https://github.com/devoption/beacon/blob/main/.releaserc.json)
- [`package.json`](https://github.com/devoption/beacon/blob/main/package.json)
- [`package-lock.json`](https://github.com/devoption/beacon/blob/main/package-lock.json)
- [`docs/RELEASING.md`](https://github.com/devoption/beacon/blob/main/docs/RELEASING.md)

## Guidance

- keep release automation derived from conventional commits on `main`
- prefer least-privilege GitHub workflow permissions
- validate Composer metadata with publish checks enabled when touching release behavior
- treat archive contents as part of the public package contract
- document assumptions that maintainers must configure outside the repository

## Common workflow

1. Update release workflow, package metadata, or release docs.
2. Validate JSON and workflow syntax locally.
3. Rebuild a Composer archive if package contents changed.
4. Run the relevant publish-readiness checks before opening the pull request.
