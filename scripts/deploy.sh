#!/usr/bin/env bash
set -Eeuo pipefail

: "${APP_DIR:?APP_DIR is required}"
ARCHIVE="${ARCHIVE:-/tmp/simopram-release.tar.gz}"
RELEASE_ID="${RELEASE_ID:-$(date -u +%Y%m%d%H%M%S)}"

[[ "$APP_DIR" == /* && "$APP_DIR" != "/" ]] || { echo "APP_DIR must be an absolute application directory."; exit 1; }
[[ "$RELEASE_ID" =~ ^[a-zA-Z0-9_-]+$ ]] || { echo "Invalid RELEASE_ID."; exit 1; }
[[ -f "$ARCHIVE" ]] || { echo "Release archive is missing."; exit 1; }
[[ -f "$APP_DIR/shared/.env" ]] || { echo "Create $APP_DIR/shared/.env before deployment."; exit 1; }

APP_DIR="$(cd "$APP_DIR" && pwd -P)"
[[ "$APP_DIR" != "/" ]] || { echo "APP_DIR cannot resolve to root."; exit 1; }
RELEASES_DIR="$APP_DIR/releases"
SHARED_DIR="$APP_DIR/shared"
CURRENT_LINK="$APP_DIR/current"
RELEASE_DIR="$RELEASES_DIR/$RELEASE_ID"
NEXT_LINK="$APP_DIR/.current-$RELEASE_ID"
PREVIOUS_RELEASE=""
MAINTENANCE=0
ACTIVATED=0

if [[ -e "$CURRENT_LINK" && ! -L "$CURRENT_LINK" ]]; then
  echo "$CURRENT_LINK must be a symlink, not a directory."
  exit 1
fi
if [[ -L "$CURRENT_LINK" ]]; then
  PREVIOUS_RELEASE="$(readlink -f "$CURRENT_LINK")"
  [[ -f "$PREVIOUS_RELEASE/artisan" ]] || { echo "Current release is invalid."; exit 1; }
fi
[[ ! -e "$RELEASE_DIR" && ! -L "$RELEASE_DIR" ]] || { echo "Release already exists: $RELEASE_ID"; exit 1; }

rollback() {
  local status=$1
  trap - ERR INT TERM EXIT
  echo "Deployment failed; restoring the previous application release. Database migrations are not reversed."
  if [[ "$ACTIVATED" == 1 ]]; then
    if [[ -n "$PREVIOUS_RELEASE" ]]; then
      ln -sfn "$PREVIOUS_RELEASE" "$NEXT_LINK"
      mv -Tf "$NEXT_LINK" "$CURRENT_LINK"
    else
      rm -f "$CURRENT_LINK"
    fi
  fi
  if [[ "$MAINTENANCE" == 1 ]]; then
    (cd "${PREVIOUS_RELEASE:-$RELEASE_DIR}" && php artisan up) || true
  fi
  rm -f "$NEXT_LINK"
  exit "$status"
}
trap 'exit 130' INT
trap 'exit 143' TERM
trap 'status=$?; if [[ $status -ne 0 ]]; then rollback "$status"; fi' EXIT

mkdir -p "$RELEASES_DIR" "$RELEASE_DIR"
mkdir -p "$SHARED_DIR/storage/app/public" "$SHARED_DIR/storage/app/private"
mkdir -p "$SHARED_DIR/storage/framework/cache/data" "$SHARED_DIR/storage/framework/sessions"
mkdir -p "$SHARED_DIR/storage/framework/views" "$SHARED_DIR/storage/logs"

echo "Preparing release $RELEASE_ID"
tar -xzf "$ARCHIVE" -C "$RELEASE_DIR"
[[ -f "$RELEASE_DIR/artisan" && -f "$RELEASE_DIR/vendor/autoload.php" && -f "$RELEASE_DIR/public/build/manifest.json" ]]
[[ ! -e "$RELEASE_DIR/.env" && ! -L "$RELEASE_DIR/.env" ]]
[[ ! -e "$RELEASE_DIR/storage" && ! -L "$RELEASE_DIR/storage" ]]
ln -s "$SHARED_DIR/storage" "$RELEASE_DIR/storage"
ln -s "$SHARED_DIR/.env" "$RELEASE_DIR/.env"
mkdir -p "$RELEASE_DIR/bootstrap/cache"
chmod -R ug+rwX "$SHARED_DIR/storage" "$RELEASE_DIR/bootstrap/cache"

cd "$RELEASE_DIR"
composer check-platform-reqs --no-dev
php artisan package:discover --no-interaction

MAINTENANCE=1
php artisan down --retry=60 --no-interaction
php artisan migrate --force --no-interaction
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction
php artisan storage:link --no-interaction

echo "Activating release"
ln -s "$RELEASE_DIR" "$NEXT_LINK"
mv -Tf "$NEXT_LINK" "$CURRENT_LINK"
ACTIVATED=1

php artisan queue:restart --no-interaction
php artisan up --no-interaction
MAINTENANCE=0
trap - ERR INT TERM EXIT
rm -f "$ARCHIVE"

echo "Removing old releases (keeping the current and previous releases)"
mapfile -t old_releases < <(find "$RELEASES_DIR" -mindepth 1 -maxdepth 1 -type d -printf '%T@ %p\n' | sort -nr | tail -n +6 | cut -d ' ' -f2-)
for old_release in "${old_releases[@]}"; do
  [[ "$old_release" == "$RELEASES_DIR/"* && "$old_release" != "$RELEASE_DIR" && "$old_release" != "$PREVIOUS_RELEASE" ]] || continue
  rm -rf -- "$old_release"
done

echo "Deployment completed: $RELEASE_DIR"
