#!/usr/bin/env bash
set -Eeuo pipefail

# Keep files/directories created by the deployment group-writable.
# Runtime ownership/permissions must still be provisioned on the VPS so both
# the deploy user and the PHP-FPM/queue user can write to shared/storage.
umask 0002

: "${APP_DIR:?APP_DIR is required}"
ARCHIVE="${ARCHIVE:-/tmp/simopram-release.tar.gz}"
RELEASE_ID="${RELEASE_ID:-$(date -u +%Y%m%d%H%M%S)}"

[[ "$APP_DIR" == /* && "$APP_DIR" != "/" ]] || {
  echo "APP_DIR must be an absolute application directory."
  exit 1
}

[[ "$RELEASE_ID" =~ ^[a-zA-Z0-9_-]+$ ]] || {
  echo "Invalid RELEASE_ID."
  exit 1
}

[[ -f "$ARCHIVE" ]] || {
  echo "Release archive is missing."
  exit 1
}

[[ -f "$APP_DIR/shared/.env" ]] || {
  echo "Create $APP_DIR/shared/.env before deployment."
  exit 1
}

APP_DIR="$(cd "$APP_DIR" && pwd -P)"
[[ "$APP_DIR" != "/" ]] || {
  echo "APP_DIR cannot resolve to root."
  exit 1
}

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
  [[ -f "$PREVIOUS_RELEASE/artisan" ]] || {
    echo "Current release is invalid."
    exit 1
  }
fi

[[ ! -e "$RELEASE_DIR" && ! -L "$RELEASE_DIR" ]] || {
  echo "Release already exists: $RELEASE_ID"
  exit 1
}

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

# -----------------------------------------------------------------------------
# Prepare release + persistent shared runtime storage.
#
# IMPORTANT:
# - shared/storage survives every release switch.
# - Livewire temporary uploads use storage/app/private/livewire-tmp.
# - Journal attachments are stored under storage/app/public/journals.
# -----------------------------------------------------------------------------
mkdir -p "$RELEASES_DIR" "$RELEASE_DIR"

mkdir -p \
  "$SHARED_DIR/storage/app/private/livewire-tmp" \
  "$SHARED_DIR/storage/app/public/journals" \
  "$SHARED_DIR/storage/framework/cache/data" \
  "$SHARED_DIR/storage/framework/sessions" \
  "$SHARED_DIR/storage/framework/views" \
  "$SHARED_DIR/storage/logs"

# Fail before maintenance/migrations if the deployment user cannot write to
# runtime storage. Server provisioning must ensure PHP-FPM/queue workers share
# compatible group/ACL access to these same directories.
for runtime_directory in \
  "$SHARED_DIR/storage/app/private" \
  "$SHARED_DIR/storage/app/private/livewire-tmp" \
  "$SHARED_DIR/storage/app/public" \
  "$SHARED_DIR/storage/app/public/journals" \
  "$SHARED_DIR/storage/framework" \
  "$SHARED_DIR/storage/framework/cache/data" \
  "$SHARED_DIR/storage/framework/sessions" \
  "$SHARED_DIR/storage/framework/views" \
  "$SHARED_DIR/storage/logs"
do
  if ! write_probe="$(mktemp "$runtime_directory/.deploy-write-XXXXXX")"; then
    echo "Deployment user cannot write to $runtime_directory."
    echo "Ask the VPS administrator to grant the deployment user and web/worker users shared group or ACL access to this directory."
    exit 1
  fi

  rm -f -- "$write_probe"
done

# -----------------------------------------------------------------------------
# Extract and validate the immutable release artifact.
# -----------------------------------------------------------------------------
echo "Preparing release $RELEASE_ID"
tar -xzf "$ARCHIVE" -C "$RELEASE_DIR"

[[ -f "$RELEASE_DIR/artisan" ]] || {
  echo "Invalid release: artisan is missing."
  exit 1
}

[[ -f "$RELEASE_DIR/vendor/autoload.php" ]] || {
  echo "Invalid release: vendor/autoload.php is missing."
  exit 1
}

[[ -f "$RELEASE_DIR/public/build/manifest.json" ]] || {
  echo "Invalid release: public/build/manifest.json is missing."
  exit 1
}

[[ ! -e "$RELEASE_DIR/.env" && ! -L "$RELEASE_DIR/.env" ]] || {
  echo "Invalid release: .env must not be packaged in the release artifact."
  exit 1
}

[[ ! -e "$RELEASE_DIR/storage" && ! -L "$RELEASE_DIR/storage" ]] || {
  echo "Invalid release: storage must not be packaged in the release artifact."
  exit 1
}

# Every release points to the same persistent storage and environment file.
ln -s "$SHARED_DIR/storage" "$RELEASE_DIR/storage"
ln -s "$SHARED_DIR/.env" "$RELEASE_DIR/.env"

mkdir -p "$RELEASE_DIR/bootstrap/cache"
chmod -R ug+rwX "$RELEASE_DIR/bootstrap/cache"

cd "$RELEASE_DIR"

composer check-platform-reqs --no-dev
php artisan package:discover --no-interaction

# -----------------------------------------------------------------------------
# Maintenance window + database/cache preparation.
# -----------------------------------------------------------------------------
MAINTENANCE=1
php artisan down --retry=60 --no-interaction
php artisan migrate --force --no-interaction
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction
php artisan storage:link --no-interaction

# -----------------------------------------------------------------------------
# Atomic release activation.
# -----------------------------------------------------------------------------
echo "Activating release"
ln -s "$RELEASE_DIR" "$NEXT_LINK"
mv -Tf "$NEXT_LINK" "$CURRENT_LINK"
ACTIVATED=1

# Sanity check: current/storage must still resolve to shared/storage.
CURRENT_STORAGE="$(readlink -f "$CURRENT_LINK/storage")"
SHARED_STORAGE="$(readlink -f "$SHARED_DIR/storage")"

if [[ "$CURRENT_STORAGE" != "$SHARED_STORAGE" ]]; then
  echo "Activated release is not using shared storage."
  echo "Current storage: $CURRENT_STORAGE"
  echo "Shared storage:  $SHARED_STORAGE"
  exit 1
fi

php artisan queue:restart --no-interaction
php artisan up --no-interaction
MAINTENANCE=0

trap - ERR INT TERM EXIT
rm -f "$ARCHIVE"

# -----------------------------------------------------------------------------
# Retain recent releases. Never delete the active or immediate previous release.
# -----------------------------------------------------------------------------
echo "Removing old releases (keeping the current and previous releases)"
mapfile -t old_releases < <(
  find "$RELEASES_DIR" \
    -mindepth 1 \
    -maxdepth 1 \
    -type d \
    -printf '%T@ %p\n' \
    | sort -nr \
    | tail -n +6 \
    | cut -d ' ' -f2-
)

for old_release in "${old_releases[@]}"; do
  [[ "$old_release" == "$RELEASES_DIR/"* \
    && "$old_release" != "$RELEASE_DIR" \
    && "$old_release" != "$PREVIOUS_RELEASE" ]] || continue

  rm -rf -- "$old_release"
done

echo "Deployment completed: $RELEASE_DIR"
