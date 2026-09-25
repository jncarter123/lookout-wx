#!/bin/sh
#
# Three roles out of one image:
#
#   web        FrankenPHP over public/, plus the migrations.
#   horizon    the queue workers (NWS feed polling and alert processing).
#   scheduler  `schedule:work`: queues the poll every minute, the prune daily.
#
# The web container owns the migrations and the key so the three never race;
# the others wait for it (compose holds them back until /up answers).

set -e

role="${1:-web}"
data_dir="${LOOKOUT_DATA_DIR:-/var/lib/lookout}"
key_file="${data_dir}/app_key"

mkdir -p "${data_dir}"

# --- APP_KEY ---------------------------------------------------------------
# Encrypts sessions and cookies. Losing it signs everyone out (API tokens are
# unaffected), so it is kept on the volume rather than regenerated each start.
if [ -z "${APP_KEY:-}" ]; then
    if [ ! -f "${key_file}" ] && [ "${role}" != "web" ]; then
        echo "lookout: waiting for the web container to write ${key_file}" >&2
        waited=0
        while [ ! -f "${key_file}" ] && [ "${waited}" -lt 60 ]; do
            sleep 1
            waited=$((waited + 1))
        done
    fi

    if [ ! -f "${key_file}" ]; then
        php artisan key:generate --show > "${key_file}"
        chmod 600 "${key_file}"
        echo "lookout: generated an APP_KEY in ${key_file}." >&2
    fi

    APP_KEY="$(cat "${key_file}")"
    export APP_KEY
fi

# --- caches ----------------------------------------------------------------
# Built here rather than at image build time: the env is only complete now.
php artisan config:cache
php artisan view:cache

# A missing route cache is a slower app, not a broken one, so it is not fatal.
if ! php artisan route:cache; then
    echo "lookout: route:cache failed, continuing without a route cache" >&2
    php artisan route:clear
fi

case "${role}" in
    web)
        php artisan migrate --force
        # Permissions are cached in Redis; a new release may change them.
        php artisan permission:cache-reset
        exec frankenphp run --config /app/docker/Caddyfile
        ;;
    horizon)
        exec php artisan horizon
        ;;
    scheduler)
        exec php artisan schedule:work
        ;;
    *)
        exec "$@"
        ;;
esac
