#!/usr/bin/env bash
set -euo pipefail
release_dir="$(mktemp -d)"
trap 'rm -rf "$release_dir"' EXIT
export COMPOSER_ROOT_VERSION="${RELEASE_VERSION:-0.0.0}"
composer validate --strict --no-check-publish
composer archive --format=zip --dir="$release_dir"
archive_file="$(find "$release_dir" -maxdepth 1 -name '*.zip' -print -quit)"
test -n "$archive_file"
if unzip -Z1 "$archive_file" | grep -Eq '(^|/)vendor/'; then
  echo 'Release archive must not contain the development vendor tree' >&2
  exit 1
fi
archive_bytes="$(wc -c < "$archive_file" | tr -d ' ')"
if [ "$archive_bytes" -gt 1000000 ]; then
  echo "Release archive is unexpectedly large: $archive_bytes bytes" >&2
  exit 1
fi
for required in LICENSE.md contracts/headless-commerce-v1.openapi.yaml src/Client.php; do
  unzip -Z1 "$archive_file" | grep -Eq "(^|/)$required$" || { echo "Release archive omitted $required" >&2; exit 1; }
done
mkdir "$release_dir/package"
unzip -q "$archive_file" -d "$release_dir/package"
cd "$release_dir/package"
composer install --no-dev --prefer-dist --no-interaction
php -r "require 'vendor/autoload.php'; if (!class_exists('Phessage\\HeadlessCommerce\\Client') || !class_exists('Phessage\\HeadlessCommerce\\WebhookVerifier')) exit(1);"
printf 'Composer archive install smoke passed: %s\n' "$(basename "$archive_file")"
