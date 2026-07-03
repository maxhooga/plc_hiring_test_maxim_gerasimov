# Hiring test starter

A minimal Laravel 11 + Vue 3 starter for the senior dev test task.

The brief is in [TASK.md](TASK.md). Fill in [NOTES.md](NOTES.md) when you're done.

## Run it

Default DB is SQLite — no setup needed. Pick one of:

### Option A — local PHP/Node

Requires PHP 8.2+, Composer, Node 20+.

```bash
cp .env.example .env
composer install
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install
npm run dev          # terminal 1
php artisan serve    # terminal 2
```

Open http://127.0.0.1:8000 — click vehicles on the left to record views and watch the trending widget update on the right.

For scheduled cache flushes in local dev, run `php artisan schedule:work` in a third terminal (optional; trending still works without it because unflushed cache counts are included in the API response).

### Option B — DDEV

Requires DDEV.

```bash
ddev start
cp .env.example .env
ddev composer install
ddev artisan key:generate
touch database/database.sqlite
ddev artisan migrate --seed
ddev npm install
ddev npm run dev
```

Open https://hiring-test.ddev.site

## What's in here

- `vehicles` table seeded with 500 random vehicles (`php artisan migrate --seed`).
- `vehicle_views` hourly aggregates (`vehicle_id`, `hour`, `count`) with a cache-backed view counter and scheduled flush.
- `GET /api/vehicles/{id}` — vehicle detail + view increment.
- `GET /api/vehicles/trending` — top 10 most-viewed vehicles in the last 24 hours.
- `GET /api/vehicles` — demo browse list for the local UI only.
- `resources/js/components/TrendingVehicles.vue` — trending widget with 30s polling.
- `tests/Feature/VehicleTrendingTest.php` — API contract tests (TDD).

SQLite is the default and needs no setup. Switch to MySQL or Redis in `.env` if your approach needs them. See `TASK.md` for the constraint on adding new packages.

## Deliverables

1. Working implementation of both endpoints + the Vue component.
2. **Automated tests** — at minimum, one meaningful test (your call: backend or frontend). Broader, runnable coverage is a **significant bonus**.
3. `NOTES.md` filled in (template provided).
4. Commit history that's easy to follow.

Push to a private GitLab/GitHub repo and share access, or send a zip.
