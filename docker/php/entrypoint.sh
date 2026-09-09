#!/usr/bin/env bash
set -euo pipefail

# Nexora backend container entrypoint.
#
# Keeps container startup dumb and explicit: it does NOT run migrations or
# composer installs automatically (that is `make setup`'s job, so startup is
# deterministic and CI-friendly). It only ensures writable runtime dirs exist.

if [ -d /var/www/html/storage ]; then
    mkdir -p \
        /var/www/html/storage/framework/{cache,sessions,testing,views} \
        /var/www/html/storage/logs \
        /var/www/html/bootstrap/cache
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
fi

exec "$@"
