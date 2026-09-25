# syntax=docker/dockerfile:1

# One image, three roles, chosen by the container's command:
#
#   web        the dashboards and API (FrankenPHP), and the migrations
#   horizon    the queue workers that poll the NWS feed and process alerts
#   scheduler  `schedule:work`, which queues the poll every minute and the
#              daily prune
#
# All three are required: without the scheduler nothing is ever polled, and
# without Horizon the polls queue up and never run.

ARG FRANKENPHP_TAG=1-php8.4-alpine
ARG NODE_TAG=22-bookworm-slim

# --- base -------------------------------------------------------------------
# pdo_mysql for the database, redis (phpredis) for queues, cache and rate
# limits, pcntl so Horizon and schedule:work stop cleanly on SIGTERM.
FROM dunglas/frankenphp:${FRANKENPHP_TAG} AS base

RUN install-php-extensions pdo_mysql redis opcache pcntl \
    && apk add --no-cache curl

WORKDIR /app

# --- composer dependencies --------------------------------------------------
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache git unzip

# Dependencies first, so a code-only change does not re-resolve the tree.
COPY composer.json composer.lock ./

# `--prefer-install=auto` keeps dist archives as the fast path but lets a
# package that will not download fall back to a git clone. COMPOSER_AUTH, when
# the builder passes it, authenticates those downloads; without it Composer
# downloads anonymously and a plain `docker build` still works.
RUN --mount=type=secret,id=composer_auth,env=COMPOSER_AUTH \
    composer install --no-dev --no-interaction --no-progress \
        --prefer-install=auto --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative \
    && composer run-script post-autoload-dump

# --- frontend ---------------------------------------------------------------
FROM node:${NODE_TAG} AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund --ignore-scripts

# app.css @sources views from vendor and classes from app/Livewire, so the CSS
# build needs both.
COPY --from=vendor /app/vendor ./vendor
COPY vite.config.js ./
COPY app/Livewire ./app/Livewire
COPY resources ./resources
COPY public ./public

RUN npm run build

# --- runtime ----------------------------------------------------------------
FROM base AS app

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    LOOKOUT_DATA_DIR=/var/lib/lookout

COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build
COPY docker/entrypoint.sh /usr/local/bin/lookout-entrypoint

# /data and /config belong to Caddy, /var/lib/lookout to us. Chowning the data
# dir in the image is what gives a fresh named volume the right owner.
RUN chmod +x /usr/local/bin/lookout-entrypoint \
    && mkdir -p storage/framework/cache/data storage/framework/sessions \
        storage/framework/views storage/logs bootstrap/cache \
        "${LOOKOUT_DATA_DIR}" \
    && chown -R www-data:www-data storage bootstrap/cache "${LOOKOUT_DATA_DIR}" /data /config

USER www-data

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8080/up || exit 1

ENTRYPOINT ["lookout-entrypoint"]
CMD ["web"]
