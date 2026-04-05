# Beacon

Production-ready Docker and Helm support for Laravel applications, with guided installation, Octane integration, and streamlined build and deploy workflows.

## What Beacon does

Beacon installs into an existing Laravel application and scaffolds the first layer of production deployment assets.

Current MVP features:

- interactive `php artisan beacon:install` command built with Laravel Prompts
- optional Laravel Octane installation when the Octane runtime is selected
- Dockerfile generation for `php-fpm` or `octane`
- Helm chart scaffolding under `charts/<application-slug>`
- managed Composer scripts for `beacon:build` and `beacon:deploy`
- Pest coverage, Laravel compatibility CI, and automated semantic releases for the package itself

Out of scope for this MVP:

- cloud-specific deployment logic
- full platform provisioning
- environment-specific infrastructure assumptions

## Requirements

- PHP `^8.3`
- Laravel `^11.0 | ^12.0 | ^13.0`

## Install

Recommended as a development dependency in the Laravel application you want to prepare:

```bash
composer require devoption/beacon --dev
```

Then run the installer:

```bash
php artisan beacon:install
```

## Install flow

Beacon currently prompts for:

- application name
- runtime: `php-fpm` or `octane`
- deployment scaffolding: `docker`, `helm`, or `docker-and-helm`
- whether Beacon should update Composer scripts

When the Octane runtime is selected, Beacon checks the target application's `composer.json` and installs `laravel/octane` if it is not already present.

## Generated files

Depending on the options you choose, Beacon generates:

- `Dockerfile`
- `charts/<application-slug>/Chart.yaml`
- `charts/<application-slug>/values.yaml`
- `charts/<application-slug>/templates/_helpers.tpl`
- `charts/<application-slug>/templates/deployment.yaml`
- `charts/<application-slug>/templates/service.yaml`
- `charts/<application-slug>/templates/ingress.yaml`

If Composer script updates are enabled, Beacon also manages:

- `beacon:build`
- `beacon:deploy`

Example managed scripts:

```json
{
  "scripts": {
    "beacon:build": "docker build --file Dockerfile --tag my-app:latest .",
    "beacon:deploy": "helm upgrade --install my-app ./charts/my-app --namespace default --create-namespace"
  }
}
```

## Rerunning the installer

Beacon is designed to be rerunnable.

- generated Beacon-managed files are rewritten with current stub output
- unchanged generated files remain unchanged on repeat runs
- Beacon-managed Composer script entries are updated without replacing unrelated user scripts

## Development

Run the test suite with:

```bash
composer test
```

Repository automation also includes:

- Laravel compatibility CI in [`.github/workflows/ci.yml`](https://github.com/devoption/beacon/blob/main/.github/workflows/ci.yml)
- publish-readiness validation in [`.github/workflows/publish-readiness.yml`](https://github.com/devoption/beacon/blob/main/.github/workflows/publish-readiness.yml)
- automated semantic releases in [`.github/workflows/release.yml`](https://github.com/devoption/beacon/blob/main/.github/workflows/release.yml)

For contributor workflow details, see [CONTRIBUTING.md](CONTRIBUTING.md).

For maintainer release and Packagist setup notes, see [docs/RELEASING.md](https://github.com/devoption/beacon/blob/main/docs/RELEASING.md).

## License

[MIT](LICENSE)
