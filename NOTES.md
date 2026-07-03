# Notes

## Approach

- View counter: `Cache::increment` on a per-vehicle, per-hour key in `VehicleViewCounter::recordView()`. The `show` endpoint only touches cache, not the database. Dirty keys are tracked in `vehicle_views:pending` and persisted by `php artisan vehicle-views:flush`, scheduled every minute.
- Trending query: `SUM(count)` from hourly `vehicle_views` rows in the last 24 hours, plus an overlay of unflushed cache counts so results stay correct between scheduled flushes. Sorted by `view_count DESC`, tie-break by lower `vehicle_id`, limited to 10. Vehicle fields are joined from `vehicles`.
- Schema: kept the starter migration and added a follow-up migration to reshape `vehicle_views` into `(vehicle_id, hour, count)` with `UNIQUE(vehicle_id, hour)` for upserts instead of one row per view.
- Component: `TrendingVehicles.vue` loads on mount, polls every 30 seconds, and handles loading/error/list states. A small demo shell (`App.vue`, `VehicleBrowser.vue`) was added so the feature can be exercised in the browser; `GET /api/vehicles` is only for that demo UI.

## Tradeoffs / what I'd do with more time

- Hourly buckets can include up to ~1 hour of views outside the rolling 24h window at bucket boundaries. Acceptable for a trending widget; 15-minute buckets would tighten that.
- Use Redis for atomic increments and durability under higher traffic; keep hourly DB buckets as the query-friendly store.
- Run the flush via a queue job with retries instead of only the scheduler.
- Add Vitest coverage for the Vue component states and polling behaviour.
- Cache the trending response briefly (e.g. 15–30s) since the frontend already polls at that interval.

## Anything I'd flag as risky in production

- Views still in cache are lost if the cache is cleared before flush — mitigated by Redis persistence or a shorter flush interval.
- The pending-key list is a simple in-cache index; at very large scale I'd use Redis sets or a dedicated index table.
- `migrate:fresh` without `--seed` leaves the demo page empty; production would not rely on seeded browse data.
- SQLite is fine for this test; production would use MySQL/Postgres and monitor hot-row upsert contention after flush.
