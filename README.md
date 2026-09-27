# Lookout

Real-time National Weather Service alert monitoring, built with Laravel. Lookout continuously polls the [NWS API](https://api.weather.gov) for all active alerts, stores them, and delivers them to the UI in real time via Reverb or Pusher and to other systems via a token-authenticated API. It covers land and marine alerts alike, with extra support for mariners: marine-area filters and point lookups that work offshore.

> [!WARNING]
> **Not an official warning source.** This project is not affiliated with or endorsed by the National Weather Service or NOAA. Alerts may be delayed, incomplete, or missing because of polling intervals, upstream outages, or bugs. Do not rely on it for safety-of-life decisions; always consult official NWS/NOAA broadcasts, NOAA Weather Radio, and VHF marine channels.

## Features

- **Active Alerts Dashboard** — View current NWS alerts filtered by marine area (Alaska, Atlantic, Great Lakes, Gulf of Mexico, Eastern Pacific, Central/Western Pacific) or specific forecast zone (e.g. `GMZ330`)
- **Historical Alerts Dashboard** — Browse past alerts with configurable time-window filters (15 min – 12 hrs)
- **Real-Time Updates** — New and updated alerts are broadcast instantly via an external Reverb server or Pusher, without page reloads
- **Queue-Based Processing** — Alert polling and processing run as background jobs via Laravel Horizon, keeping the UI responsive
- **Geographic Filtering** — Resolve alerts by county (UGC code) or lat/lon coordinates via built-in API endpoints; offshore points match their marine forecast zone
- **HTTP Caching** — NWS API responses are cached with ETag/Last-Modified support to minimize redundant requests
- **Authentication** — Email/password login with session-based auth for the web UI (Livewire login page)
- **Role-Based Access Control** — Granular permissions assigned to roles via Spatie Laravel Permission; enforced on all web routes and API endpoints
- **User Management** — Admin interface (Livewire) for creating, editing, and deleting users with role assignment
- **Role Management** — Admin interface for creating roles and toggling permissions via checkbox UI
- **API Token Management** — Personal access tokens via Laravel Sanctum with configurable expiry (1 month, 3 months, or no expiry); users with `tokens.manage-own` can manage their own tokens; admins with `tokens.manage` can manage all tokens
- **Activity Logging** — All user, role, and permission changes are recorded via Spatie Activity Log

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13 (PHP 8.4+) |
| UI Components | Livewire 4 |
| Frontend | Tailwind CSS, Alpine.js, Vite |
| Real-Time | Reverb (external) or Pusher + Laravel Echo |
| Queue / Cache | Redis + Laravel Horizon |
| HTTP Client | Saloon (with rate limiting) |
| Database | MySQL |
| Auth | Laravel Sanctum (API tokens) |
| RBAC | Spatie Laravel Permission |
| Audit Log | Spatie Activity Log |
| Error Tracking | Sentry |

## Requirements

- PHP 8.4+
- Composer
- Node.js / npm
- MySQL
- Redis
- A Reverb server (e.g. Soundboard) or a Pusher account, for live updates

## Running with Docker

One image runs as three containers from [`compose.yaml`](compose.yaml), alongside MySQL and Redis: the web app, the Horizon queue workers, and the scheduler that queues the NWS poll every minute.

```bash
curl -fsSLO https://raw.githubusercontent.com/jncarter123/lookout-wx/main/compose.yaml
curl -fsSL -o docker.env https://raw.githubusercontent.com/jncarter123/lookout-wx/main/docker.env.example
echo "LOOKOUT_IMAGE=jncarter/lookout-wx:1" > .env    # pull instead of build
echo "DB_PASSWORD=$(openssl rand -hex 16)" >> .env    # MySQL password, used by both services

# edit docker.env: set APP_URL and NWS_API_USER_AGENT (and Reverb or Pusher for live updates)
docker compose up -d
docker compose exec app php artisan db:seed --force   # admin@example.com; the password is printed once
```

The app listens on `127.0.0.1:8000`, plain HTTP on loopback only, for a reverse proxy in front to terminate TLS. Set `APP_URL` in `docker.env` to the public URL, scheme included (`https://lookout.example.com`), or the browser blocks its assets as mixed content.

- **Live updates** need an external Reverb server (e.g. Soundboard) or a Pusher Channels app. For Reverb, create an app for Lookout on it, allow Lookout's origin, and set `BROADCAST_CONNECTION=reverb` with `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET` and the server's public `REVERB_HOST`/`REVERB_PORT`/`REVERB_SCHEME`. For Pusher, set `BROADCAST_CONNECTION=pusher` and the `PUSHER_*` keys. Without either, Lookout still ingests alerts, and dashboards show them on the next page load.
- **Data** lives in the `mysql-data` volume. The `APP_KEY` is generated onto the `lookout-data` volume on first start; it only protects sessions, so losing it just signs everyone out.
- **Upgrades:** `docker compose pull && docker compose up -d`. Migrations run when the web container starts.

Images are published to Docker Hub as [`jncarter/lookout-wx`](https://hub.docker.com/r/jncarter/lookout-wx) for `linux/amd64` and `linux/arm64`, tagged by version (`1.0.0`, `1.0`, `1`), `latest`, and commit SHA. To build from source instead, clone the repository, `cp docker.env.example docker.env`, and run `docker compose up -d --build`.

## Installation

```bash
git clone https://github.com/jncarter123/lookout-wx.git
cd lookout-wx

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Configure your `.env` file (see [Environment Variables](#environment-variables)), then:

```bash
php artisan migrate
php artisan db:seed          # seeds roles, permissions, and an initial admin user
npm run build
```

The seeder creates `admin@example.com` with a random password printed to the console once. Change the email and password after first login.

## Environment Variables

All variables are documented in [`.env.example`](.env.example). The ones you must set:

| Variable | Purpose |
|---|---|
| `NWS_API_USER_AGENT` | NWS requires a descriptive User-Agent with contact info, e.g. `"lookout-wx (you@example.com)"` |
| `DB_*` | MySQL connection |
| `REDIS_*` | Redis connection (queues, caching, rate limiting) |
| `REVERB_*` or `PUSHER_APP_*` | Credentials for real-time updates (see [Running with Docker](#running-with-docker)) |

Sentry (`SENTRY_LARAVEL_DSN`) and Laravel Nightwatch (`NIGHTWATCH_TOKEN`) are optional. So are `NWS_COMPAT_RATE_LIMIT` and `NWS_COMPAT_CACHE_SECONDS`, for the [NWS-compatible endpoints](#nws-compatible-endpoints).

## Running the Application

Start all required processes (web server, queue workers, Vite dev server):

```bash
# Web server
php artisan serve

# Queue workers (required for alert polling)
php artisan horizon

# Frontend dev server
npm run dev
```

## Alert Polling

Alerts are polled from the NWS active alerts Atom feed on a 30-second rate limit. To trigger a manual poll:

```bash
# Poll all alerts
php artisan nws:poll-alerts

# Poll alerts for a specific state
php artisan nws:poll-alerts --area=TX
```

In production, configure the Laravel scheduler in cron to automate polling:

```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

## Roles & Permissions

Permissions are defined in `config/auth_permissions.php` and seeded automatically:

| Permission | Description |
|---|---|
| `alerts.read` | View alert dashboards |
| `users.read` | View user list |
| `users.create` | Create users |
| `users.update` | Edit users |
| `users.delete` | Delete users |
| `roles.read` | View roles |
| `roles.create` | Create roles |
| `roles.update` | Edit roles |
| `roles.delete` | Delete roles |
| `tokens.manage` | Manage all users' API tokens |
| `tokens.manage-own` | Manage own API tokens |

Roles are managed at `/admin/roles`. The seeder creates an initial `admin` user — see `database/seeders/AdminUserSeeder.php` for credentials.

## API Authentication

All API endpoints except the [NWS-compatible ones](#nws-compatible-endpoints) require a Bearer token issued via the token management UI (`/admin/tokens`) or programmatically via Sanctum.

```http
Authorization: Bearer <your-token>
```

## API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/alerts/changes?since=&limit=` | Cursor feed of alert changes for syncing all alerts downstream (see below) |
| GET | `/api/alerts/county/{ugc}` | Alerts for a county by UGC code (e.g. `TXC121`) |
| GET | `/api/alerts/points?lat=&lon=` | Alerts for geographic coordinates, matched by county or forecast zone (works offshore) |
| GET | `/api/geo/points?lat=&lon=` | Geographic metadata for a lat/lon point |
| GET | `/api/geo/ugc?lat=&lon=` | County UGC code for a lat/lon point |

### Alert changes feed

For consumers that keep their own copy of alerts (e.g. sv-cad) and filter by area locally.

1. **Bootstrap:** `GET /api/alerts/changes` (no `since`) returns every active alert and a `cursor`.
2. **Poll:** `GET /api/alerts/changes?since=<cursor>` returns changes after the cursor and a new `cursor`. If `hasMore` is true, request again immediately. `limit` defaults to 500 (max 1000).
3. **Resync:** a `410` means the cursor is older than the retained history (48 hours); start again from step 1.

Each change is `{type, alertId, alert}`: `type` is `upserted` or `removed`, and `alert` is the alert's *current* state including `active`, `counties`, and `zones`, so applying a change twice is harmless. `alert` is `null` if the alert has since been pruned. Changes are held back for a few seconds after they are written so a cursor never skips a late-committing row.

### NWS-compatible endpoints

Drop-in replacements for the [NWS API](https://www.weather.gov/documentation/services-web-api)'s alert endpoints, answered from Lookout's store. An application already calling `api.weather.gov` switches by changing its base URL to `https://{lookout host}/api/nws` — no token, and the same response shape.

| Method | Endpoint | As NWS |
|---|---|---|
| GET | `/api/nws/alerts/active` | `/alerts/active`, with `point`, `area`, `zone`, `status`, `message_type`, `event`, `severity`, `urgency`, `certainty`, `limit` |
| GET | `/api/nws/alerts/{id}` | `/alerts/{id}` |

- **Public and rate limited** per client IP, like NWS: `NWS_COMPAT_RATE_LIMIT` requests a minute (default 60). Identical queries are cached for `NWS_COMPAT_CACHE_SECONDS` (default 30).
- **Same shape:** a GeoJSON `FeatureCollection` (`application/geo+json`) of each alert exactly as NWS published it; errors are `application/problem+json`.
- **Refused, not ignored:** any other NWS parameter (`region`, `code`…) is a `400`, since ignoring a filter would return more alerts than asked for.
- **Points** match the point's county and forecast zone, where NWS also matches alert polygons: a polygon warning clipping part of a county is returned for the whole county — more alerts than NWS, never fewer. A point that cannot be looked up is a `503`, never an empty list.

## Admin UI

All admin routes are protected by the `auth` middleware. Additional permission checks are enforced per route.

| Route | Permission Required | Description |
|---|---|---|
| `/admin/home` | (authenticated) | Dashboard home |
| `/admin/alerts` | `alerts.read` | Historical alerts |
| `/admin/alerts/active` | `alerts.read` | Active alerts |
| `/admin/users` | `users.read` | User management |
| `/admin/roles` | `roles.read` | Role management |
| `/admin/tokens` | `tokens.manage` or `tokens.manage-own` | API token management |

## API Documentation

Interactive API docs are generated by [Scribe](https://scribe.knuckles.wtf/laravel/) and served at `/docs`.

To regenerate after changing routes or annotations:

```bash
php artisan scribe:generate
```

The generated docs include all authenticated API endpoints with request/response examples.

## Queue Configuration

Two named queues are used:

- `polling` — NWS feed polling jobs
- `processing` — Individual alert fetch and upsert jobs

These are configured in `config/horizon.php`. Horizon's dashboard is available at `/horizon` (authenticated).

## Testing

Tests use in-memory SQLite, but some need a running Redis.

```bash
php artisan test
# or
./vendor/bin/pest
```

## Contributing

Issues and pull requests are welcome. Please include tests for behaviour changes, and make sure `./vendor/bin/pest` passes and `./vendor/bin/pint` has been run (CI checks both). To report a security vulnerability, see [SECURITY.md](SECURITY.md) instead of opening a public issue.

## License

[MIT](LICENSE) © Jeremy Carter

Alert data is provided by the [National Weather Service API](https://www.weather.gov/documentation/services-web-api).
