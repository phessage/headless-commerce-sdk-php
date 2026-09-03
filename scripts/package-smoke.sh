#!/usr/bin/env bash
set -euo pipefail
release_dir="$(mktemp -d)"
trap 'rm -rf "$release_dir"' EXIT
export COMPOSER_ROOT_VERSION="${RELEASE_VERSION:-0.0.0}"
composer validate --strict --no-check-publish
composer archive --format=zip --dir="$release_dir"
archive_file="$(find "$release_dir" -maxdepth 1 -name '*.zip' -print -quit)"
test -n "$archive_file"
mkdir "$release_dir/package"
unzip -q "$archive_file" -d "$release_dir/package"
cd "$release_dir/package"
composer install --no-dev --prefer-dist --no-interaction
php -r "require 'vendor/autoload.php'; if (!class_exists('Phessage\\HeadlessCommerce\\Client') || !class_exists('Phessage\\HeadlessCommerce\\WebhookVerifier')) exit(1);"
printf 'Composer archive install smoke passed: %s\n' "$(basename "$archive_file")"
