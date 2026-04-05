# Releasing Beacon

Beacon uses automated semantic versioning on `main`.

## How releases are cut

1. Merge conventional commits into `main`.
2. GitHub Actions runs the regular CI workflow and the publish-readiness workflow.
3. On every push to `main`, the `Release` workflow runs `semantic-release`.
4. If the commit history contains a releasable change, `semantic-release` creates:
   - a Git tag in the format `vX.Y.Z`
   - a GitHub Release with generated notes
5. Packagist picks up the new tag and publishes the new Composer version once repository updates are configured correctly.

If there are no releasable commits on `main`, the release workflow exits without creating a tag or release.

## Version mapping

Beacon follows Conventional Commits:

- `fix:` creates a patch release
  - Example: `1.2.3` -> `1.2.4`
- `feat:` creates a minor release
  - Example: `1.2.3` -> `1.3.0`
- breaking changes create a major release
  - Use either `type!: ...` such as `feat!: change install flow`
  - Or include a `BREAKING CHANGE:` footer in the commit body

Commits that do not map to a release type, such as most `docs:` or `chore:` changes, do not create a new version by themselves.

## GitHub maintainer setup

Beacon's release automation assumes:

- GitHub Actions is enabled for the repository
- the default branch is `main`
- maintainers merge conventional commits into `main`
- the built-in `GITHUB_TOKEN` has permission to create tags and GitHub Releases

The release workflow in [`../.github/workflows/release.yml`](../.github/workflows/release.yml) currently uses the repository `GITHUB_TOKEN` with `contents: write`.

### Important limitation

GitHub does not trigger additional workflow runs from events created by the default `GITHUB_TOKEN`, except for `workflow_dispatch` and `repository_dispatch`. In practice, that means a release created by Beacon's current workflow should not be expected to trigger other release-driven workflows automatically.

If maintainers later need release-created events to trigger downstream automation, switch the workflow to a dedicated secret such as `GH_TOKEN` backed by a fine-grained personal access token or GitHub App token with the required repository permissions.

## Packagist setup

Before the first public release:

1. Create or sign in to the Packagist account that will manage `devoption/beacon`.
2. Submit the repository URL for this GitHub repository on Packagist.
3. Confirm Packagist detects the package name as `devoption/beacon`.
4. Enable automatic updates for the package so new Git tags are synced from GitHub without a manual "update" action.

Recommended verification after setup:

- create or merge a releasable commit to `main`
- confirm GitHub creates a `vX.Y.Z` tag and Release
- confirm the same version appears on Packagist

If Packagist does not refresh automatically, verify the repository integration or webhook configuration in Packagist and GitHub, then trigger a manual update once before trying another release.

## Publish-readiness checks

Before release automation runs on `main`, Beacon also validates package publishability through [`../.github/workflows/publish-readiness.yml`](../.github/workflows/publish-readiness.yml).

That workflow checks:

- Composer metadata with publish checks enabled
- Composer archive creation
- dist archive contents, including required runtime files and exclusion of maintainer-only files

## Maintainer checklist

Use this checklist when preparing the first release:

- Packagist package submitted and connected to the GitHub repository
- automatic Packagist updates enabled
- GitHub Actions enabled on the repository
- `main` receiving conventional commits
- CI, publish-readiness, and release workflows green on the default branch
