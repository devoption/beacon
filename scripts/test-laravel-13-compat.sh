#!/usr/bin/env bash

set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
temp_dir="$(mktemp -d "${TMPDIR:-/tmp}/beacon-laravel13-compat.XXXXXX")"

cleanup() {
  rm -rf "${temp_dir}"
}

trap cleanup EXIT

composer_cmd=()
herd_composer="${HOME}/Library/Application Support/Herd/bin/composer"
php_bin=""

require_supported_php() {
  local candidate="$1"
  local php_version=""

  if ! php_version="$("${candidate}" -r 'echo PHP_VERSION;' 2>/dev/null)"; then
    echo "Unable to execute PHP binary '${candidate}' for local Laravel 13 verification." >&2
    exit 1
  fi

  if ! "${candidate}" -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);' >/dev/null 2>&1; then
    echo "Laravel 13 compatibility tests require PHP 8.3 or newer. Found PHP ${php_version} at '${candidate}'." >&2
    exit 1
  fi
}

if [[ -n "${PHP_BIN:-}" ]]; then
  if ! php_bin="$(command -v "${PHP_BIN}")"; then
    echo "Configured PHP_BIN '${PHP_BIN}' was not found. Set PHP_BIN to a PHP 8.3+ binary." >&2
    exit 1
  fi
elif command -v php84 >/dev/null 2>&1; then
  php_bin="$(command -v php84)"
elif command -v php >/dev/null 2>&1; then
  php_bin="$(command -v php)"
fi

if [[ -n "${php_bin}" ]]; then
  require_supported_php "${php_bin}"
fi

if [[ -n "${php_bin}" && -f "${herd_composer}" ]]; then
  composer_cmd=("${php_bin}" "${herd_composer}")
elif command -v composer >/dev/null 2>&1; then
  composer_cmd=("$(command -v composer)")
else
  echo "Unable to find a usable Composer command for local Laravel 13 verification. Ensure Composer is installed and PHP 8.3+ is available." >&2
  exit 1
fi

tar -C "${root_dir}" \
  --exclude='./.git' \
  --exclude='./vendor' \
  --exclude='./node_modules' \
  --exclude='./build' \
  --exclude='./.phpunit.cache' \
  -cf - . | tar -xf - -C "${temp_dir}"

pushd "${temp_dir}" >/dev/null

"${composer_cmd[@]}" update \
  --no-interaction \
  --no-progress \
  --prefer-dist \
  --with "illuminate/console:^13.0" \
  --with "illuminate/process:^13.0" \
  --with "illuminate/support:^13.0" \
  --with "orchestra/testbench:^11.0"

"${composer_cmd[@]}" test

popd >/dev/null
