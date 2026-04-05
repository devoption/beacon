#!/usr/bin/env bash

set -euo pipefail

archive_path="${1:-}"

if [[ -z "${archive_path}" ]]; then
  echo "Usage: $0 <archive-path>" >&2
  exit 1
fi

if [[ ! -f "${archive_path}" ]]; then
  echo "Archive not found: ${archive_path}" >&2
  exit 1
fi

required_entries=(
  "composer.json"
  "LICENSE"
  "src/BeaconServiceProvider.php"
  "stubs/docker/php-fpm.Dockerfile.stub"
  "stubs/helm/Chart.yaml.stub"
)

forbidden_patterns=(
  "^\\.gitattributes$"
  "^\\.github/"
  "^tests/"
  "^vendor/"
  "^node_modules/"
  "^build/"
  "^package\\.json$"
  "^package-lock\\.json$"
  "^\\.releaserc\\.json$"
  "^phpunit\\.xml\\.dist$"
)

archive_entries="$(unzip -Z1 "${archive_path}")"

for entry in "${required_entries[@]}"; do
  if ! grep -Fxq "${entry}" <<< "${archive_entries}"; then
    echo "Missing required archive entry: ${entry}" >&2
    exit 1
  fi
done

for pattern in "${forbidden_patterns[@]}"; do
  if grep -Eq "${pattern}" <<< "${archive_entries}"; then
    echo "Found forbidden archive entry matching pattern: ${pattern}" >&2
    exit 1
  fi
done

echo "Archive sanity checks passed for ${archive_path}"
