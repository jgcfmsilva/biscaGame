#!/usr/bin/env bash

set -u

project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
status=0
use_frontend_dev=0
stop_services=0

usage() {
  echo "Usage: $0 [--frontend-dev] [--stop]"
  exit 2
}

while [ $# -gt 0 ]; do
  case "$1" in
    --frontend-dev) use_frontend_dev=1 ;;
    --stop) stop_services=1 ;;
    *) usage ;;
  esac
  shift
done

run_step() {
  local name="$1"
  shift
  echo "==> ${name}"
  if "$@"; then
    echo "OK: ${name}"
  else
    echo "ERROR: ${name}"
    status=1
  fi
  echo
}

if [ "$stop_services" -eq 1 ]; then
  run_step "API (stop)" bash -lc "cd \"$project_root/api\" && docker compose stop"
  if [ "$use_frontend_dev" -eq 1 ]; then
    run_step "Frontend (stop dev)" bash -lc "cd \"$project_root/frontend\" && docker compose -f docker-compose.dev.yml stop"
  else
    run_step "Frontend (stop prod)" bash -lc "cd \"$project_root/frontend\" && docker compose stop"
  fi
  run_step "Websocket (stop)" bash -lc "cd \"$project_root/websocket\" && docker compose stop"
else
  run_step "API dependencies (first time only)" bash -lc "cd \"$project_root/api\" && if [ ! -f vendor/autoload.php ]; then docker compose build laravel.test && docker compose run --rm -w /var/www/html laravel.test composer install; else echo 'Composer dependencies already installed.'; fi"
  run_step "API (Docker Compose)" bash -lc "cd \"$project_root/api\" && docker compose up -d --build"
  if [ "$use_frontend_dev" -eq 1 ]; then
    run_step "Frontend (Dev / Hot Reload)" bash -lc "cd \"$project_root/frontend\" && docker compose -f docker-compose.dev.yml up -d --build"
  else
    run_step "Frontend (Prod Build)" bash -lc "cd \"$project_root/frontend\" && docker compose up -d --build"
  fi
  run_step "Websocket" bash -lc "cd \"$project_root/websocket\" && docker compose up -d --build"
fi

if [ "$status" -ne 0 ]; then
  echo "One or more steps failed."
elif [ "$stop_services" -eq 1 ]; then
  echo "All services stopped successfully."
else
  echo "All services started successfully."
fi

exit "$status"
