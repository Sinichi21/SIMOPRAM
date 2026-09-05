#!/usr/bin/env bash
set -euo pipefail

: "${1:?Pass an archive path outside the repository}"
ARCHIVE="$1"
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
ARCHIVE_DIR="$(cd "$(dirname "$ARCHIVE")" && pwd -P)"
[[ "$ARCHIVE_DIR" != "$PROJECT_DIR" && "$ARCHIVE_DIR" != "$PROJECT_DIR/"* ]] || { echo "Archive must be outside the repository."; exit 1; }

cd "$PROJECT_DIR"
test -f public/build/manifest.json
test -f vendor/autoload.php
tar -czf "$ARCHIVE" \
  --exclude='bootstrap/cache/*.php' \
  --exclude='public/hot' \
  --exclude='public/storage' \
  --exclude='public/fonts-manifest.dev.json' \
  --exclude='*.sqlite' \
  --exclude='*.sqlite-*' \
  app bootstrap config database public resources routes vendor \
  artisan composer.json composer.lock package.json package-lock.json
