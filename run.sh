#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FRONTEND_DIR="$ROOT_DIR/jekyll-fontend"
BACKEND_DIR="$ROOT_DIR/backend"
FRONTEND_HOST="${FRONTEND_HOST:-127.0.0.1}"
FRONTEND_PORT="${FRONTEND_PORT:-1111}"
BACKEND_HOST="${BACKEND_HOST:-127.0.0.1}"
BACKEND_PORT="${BACKEND_PORT:-8080}"

require_command() {
  command -v "$1" >/dev/null 2>&1 || {
    printf 'Error: command not found: %s\n' "$1" >&2
    exit 1
  }
}

require_command php
require_command bundle

[[ -f "$BACKEND_DIR/.env" ]] || {
  printf 'Error: missing %s\n' "$BACKEND_DIR/.env" >&2
  printf 'Copy backend/.env.sample to backend/.env and fill in the values first.\n' >&2
  exit 1
}

[[ -f "$BACKEND_DIR/vendor/autoload.php" ]] || {
  printf 'Error: PHP dependencies are missing. Run composer install in backend/.\n' >&2
  exit 1
}

cleanup() {
  trap - TERM INT EXIT
  kill 0 2>/dev/null || true
}
trap cleanup TERM INT EXIT

export LOCAL_URL="http://${BACKEND_HOST}:${BACKEND_PORT}"

printf 'Frontend: http://%s:%s\n' "$FRONTEND_HOST" "$FRONTEND_PORT"
printf 'Backend:  http://%s:%s\n' "$BACKEND_HOST" "$BACKEND_PORT"

(
  cd "$BACKEND_DIR"
  exec php -S "${BACKEND_HOST}:${BACKEND_PORT}" -t .
) &

(
  cd "$FRONTEND_DIR"
  exec bundle exec jekyll serve \
    --host "$FRONTEND_HOST" \
    --port "$FRONTEND_PORT" \
    --livereload false
) &

wait -n
status=$?
printf 'A service stopped (exit status %s); stopping the other service.\n' "$status" >&2
exit "$status"
