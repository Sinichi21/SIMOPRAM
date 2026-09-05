#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"
TEST_DIR="$(mktemp -d)"
trap 'rm -rf -- "$TEST_DIR"' EXIT

fail() { echo "FAIL: $*" >&2; exit 1; }

mkdir -p "$TEST_DIR/project/scripts" "$TEST_DIR/bin"
for directory in app bootstrap/cache config database public/build resources routes vendor; do
  mkdir -p "$TEST_DIR/project/$directory"
done
for file in artisan composer.json composer.lock package.json package-lock.json vendor/autoload.php public/build/manifest.json; do
  printf '{}\n' > "$TEST_DIR/project/$file"
done
touch "$TEST_DIR/project/.env" "$TEST_DIR/project/database/database.sqlite"
touch "$TEST_DIR/project/bootstrap/cache/config.php" "$TEST_DIR/project/public/hot"
mkdir -p "$TEST_DIR/project/storage/app/private" "$TEST_DIR/project/public/storage"
touch "$TEST_DIR/project/storage/app/private/secret.txt" "$TEST_DIR/project/public/storage/upload.txt"
cp "$SCRIPT_DIR/package-release.sh" "$TEST_DIR/project/scripts/"
bash "$TEST_DIR/project/scripts/package-release.sh" "$TEST_DIR/release.tar.gz"
tar -tzf "$TEST_DIR/release.tar.gz" > "$TEST_DIR/archive-files"
grep -Fxq 'public/build/manifest.json' "$TEST_DIR/archive-files" || fail 'Build missing from archive'
if grep -Eq '(^\.env$|database\.sqlite|bootstrap/cache/config\.php|public/hot|public/storage|^storage/)' "$TEST_DIR/archive-files"; then
  fail 'Archive contains environment, runtime data, or cached configuration'
fi
if bash "$TEST_DIR/project/scripts/package-release.sh" "$TEST_DIR/project/release.tar.gz"; then
  fail 'Packaging inside repository should be rejected'
fi

cat > "$TEST_DIR/bin/composer" <<'SH'
#!/usr/bin/env bash
set -euo pipefail
[[ "$*" == 'check-platform-reqs --no-dev' ]]
SH
cat > "$TEST_DIR/bin/php" <<'SH'
#!/usr/bin/env bash
set -euo pipefail
printf '%s:%s\n' "$PWD" "$*" >> "$COMMAND_LOG"
if [[ "${FAIL_COMMAND:-}" == "$2" && "$PWD" == */new ]]; then
  exit 42
fi
case "$2" in
  down) touch storage/framework/down ;;
  up) rm -f storage/framework/down ;;
esac
SH
export REAL_CHMOD="$(command -v chmod)"
export REAL_MKTEMP="$(command -v mktemp)"
cat > "$TEST_DIR/bin/chmod" <<'SH'
#!/usr/bin/env bash
set -euo pipefail
for argument in "$@"; do
  if [[ "$argument" == */shared/storage* ]]; then
    echo 'chmod: runtime files belong to the web process: Operation not permitted' >&2
    exit 1
  fi
done
exec "$REAL_CHMOD" "$@"
SH
cat > "$TEST_DIR/bin/mktemp" <<'SH'
#!/usr/bin/env bash
set -euo pipefail
if [[ "${FAIL_COMMAND:-}" == storage-unwritable && "$*" == */framework/sessions/* ]]; then
  echo 'mktemp: Permission denied' >&2
  exit 1
fi
exec "$REAL_MKTEMP" "$@"
SH
chmod +x "$TEST_DIR/bin/php" "$TEST_DIR/bin/composer" "$TEST_DIR/bin/chmod" "$TEST_DIR/bin/mktemp"
export PATH="$TEST_DIR/bin:$PATH"

for scenario in success migrate queue:restart up missing-env storage-unwritable; do
  case_dir="$TEST_DIR/$scenario"
  mkdir -p "$case_dir/shared/storage/framework" "$case_dir/releases/old"
  printf 'preserve this environment\n' > "$case_dir/shared/.env"
  touch "$case_dir/releases/old/artisan"
  ln -s "$case_dir/shared/storage" "$case_dir/releases/old/storage"
  ln -s "$case_dir/releases/old" "$case_dir/current"
  cp "$TEST_DIR/release.tar.gz" "$case_dir/archive.tar.gz"
  if [[ "$scenario" == missing-env ]]; then
    rm "$case_dir/shared/.env"
  fi

  status=0
  APP_DIR="$case_dir" ARCHIVE="$case_dir/archive.tar.gz" RELEASE_ID=new \
    FAIL_COMMAND="$scenario" COMMAND_LOG="$case_dir/commands.log" \
    bash "$SCRIPT_DIR/deploy.sh" > "$case_dir/output.log" 2>&1 || status=$?

  if [[ "$scenario" == success ]]; then
    [[ "$status" == 0 ]] || { cat "$case_dir/output.log"; fail 'Successful deploy rejected'; }
    [[ "$(readlink "$case_dir/current")" == "$case_dir/releases/new" ]] || fail 'New release not activated'
    [[ -L "$case_dir/current/.env" && -L "$case_dir/current/storage" ]] || fail 'Shared data links missing'
    [[ ! -f "$case_dir/archive.tar.gz" ]] || fail 'Uploaded archive not cleaned up'
  else
    [[ "$status" != 0 ]] || fail "Failure hidden: $scenario"
    [[ "$(readlink "$case_dir/current")" == "$case_dir/releases/old" ]] || fail "Old release not restored: $scenario"
  fi
  if [[ "$scenario" == storage-unwritable ]]; then
    grep -Fq 'Deployment user cannot write to' "$case_dir/output.log" || fail 'Missing actionable storage error'
    [[ ! -f "$case_dir/commands.log" ]] || fail 'Artisan ran before checking storage access'
  fi
  [[ ! -f "$case_dir/shared/storage/framework/down" ]] || fail "Application left in maintenance: $scenario"
  [[ -f "$case_dir/releases/old/artisan" ]] || fail "Previous release deleted: $scenario"
  if [[ "$scenario" != missing-env ]]; then
    grep -Fxq 'preserve this environment' "$case_dir/shared/.env" || fail 'Server environment overwritten'
  fi
  echo "PASS: $scenario"
done

echo 'Deployment packaging, activation, and rollback tests passed.'
