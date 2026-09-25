# Lookout

Real-time National Weather Service alert monitoring, built with Laravel. Lookout continuously polls the [NWS API](https://api.weather.gov) for all active alerts, stores them, and delivers them to the UI in real time via Pusher and to other systems via a token-authenticated API. It covers land and marine alerts alike, with extra support for mariners: marine-area filters and point lookups that work offshore.

> [!WARNING]
> **Not an official warning source.** This project is not affiliated with or endorsed by the National Weather Service or NOAA. Alerts may be delayed, incomplete, or missing because of polling intervals, upstream outages, or bugs. Do not rely on it for safety-of-life decisions; always consult official NWS/NOAA broadcasts, NOAA Weather Radio, and VHF marine channels.

## Features

- **Active Alerts Dashboard** — View current NWS alerts filtered by marine area (Alaska, Atlantic, Great Lakes, Gulf of Mexico, Eastern Pacific, Central/Western Pacific) or specific forecast zone (e.g. `GMZ330`)
- **Historical Alerts Dashboard** — Browse past alerts with configurable time-window filters (15 min – 12 hrs)
- **Real-Time Updates** — New and updated alerts are broadcast instantly via Pusher without page reloads
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
| Real-Time | Pusher + Laravel Echo |
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
- Pusher account

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
| `PUSHER_APP_*` | Pusher credentials for real-time updates |

Sentry (`SENTRY_LARAVEL_DSN`) and Laravel Nightwatch (`NIGHTWATCH_TOKEN`) are optional.

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

All API endpoints require a Bearer token issued via the token management UI (`/admin/tokens`) or programmatically via Sanctum.

```http
Authorization: Bearer <your-token>
```

## API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/alerts/county/{ugc}` | Alerts for a county by UGC code (e.g. `TXC121`) |
| GET | `/api/alerts/points?lat=&lon=` | Alerts for geographic coordinates, matched by county or forecast zone (works offshore) |
| GET | `/api/geo/points?lat=&lon=` | Geographic metadata for a lat/lon point |
| GET | `/api/geo/ugc?lat=&lon=` | County UGC code for a lat/lon point |

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
