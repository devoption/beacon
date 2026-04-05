# Contributing

Beacon uses an issue-first workflow.

## Workflow

1. Start from an open GitHub issue.
2. Create a dedicated branch from `main`.
3. Implement only that issue on the branch.
4. Add or update tests when behavior changes.
5. Use conventional commits.
6. Open a pull request for review.

Keep issues and branches small enough to complete independently.

## Branch naming

Use predictable branch names tied to the issue number:

- `feat/<issue-number>-short-description`
- `fix/<issue-number>-short-description`
- `chore/<issue-number>-short-description`
- `docs/<issue-number>-short-description`
- `test/<issue-number>-short-description`

Examples:

- `feat/10-octane-installer`
- `docs/15-readme-and-contributing`
- `chore/20-pre-publish-validation-workflow`

## Commits

Commits should be atomic and use Conventional Commits.

Examples:

- `feat: add beacon install command skeleton`
- `fix: tolerate setup failures in experimental ci lanes`
- `docs: add release and Packagist guide`
- `test: add coverage for install configuration collector`

Versioning impact:

- `fix:` -> patch release
- `feat:` -> minor release
- breaking changes -> major release

Breaking changes should use either:

- `type!: description`
- or a `BREAKING CHANGE:` footer in the commit body

## Tests and validation

Before opening or updating a pull request, run the checks that match your change.

Common commands:

```bash
composer test
composer run validate-package
composer run validate-package-publish
```

If your change affects release or packaging behavior, also validate the relevant GitHub workflow inputs locally when practical.

## Scope guidance

Please keep Beacon within the current MVP scope unless the issue explicitly expands it.

Current MVP areas include:

- package service provider and install command
- prompt-driven install flow
- Octane integration
- Dockerfile generation
- Helm chart generation
- Composer script updates
- testing, CI, release automation, and documentation

Out-of-scope examples:

- cloud-vendor-specific deployment logic
- full infrastructure provisioning
- unrelated Laravel application scaffolding

## Release notes

Maintainers should use [docs/RELEASING.md](https://github.com/devoption/beacon/blob/main/docs/RELEASING.md) for release automation and Packagist setup details.

## Laravel Boost

Beacon also ships Laravel Boost guidance for package users and contributors.

See [docs/LARAVEL_BOOST.md](https://github.com/devoption/beacon/blob/main/docs/LARAVEL_BOOST.md) for the available Beacon-specific skills and when to use them.
