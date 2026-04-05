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

## Primary files

- `.github/workflows/release.yml`
- `.github/workflows/publish-readiness.yml`
- `.releaserc.json`
- `package.json`
- `package-lock.json`
- `composer.json`
- `.gitattributes`
- `docs/RELEASING.md`

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
