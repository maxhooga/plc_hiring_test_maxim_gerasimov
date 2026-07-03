<?php

namespace App\Services;

use App\Models\Vehicle;
use App\Models\VehicleView;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VehicleViewCounter
{
    private const PENDING_KEY = 'vehicle_views:pending';

    public function recordView(int $vehicleId, ?Carbon $at = null): void
    {
        $at ??= now();
        $key = $this->cacheKey($vehicleId, $at);

        Cache::add($key, 0, $at->copy()->addHours(25));
        Cache::increment($key);

        $pending = Cache::get(self::PENDING_KEY, []);
        $pending[] = $key;
        Cache::put(self::PENDING_KEY, array_values(array_unique($pending)));
    }

    public function flush(): int
    {
        $pending = Cache::pull(self::PENDING_KEY, []);
        $flushed = 0;

        foreach ($pending as $key) {
            $parsed = $this->parseCacheKey($key);
            if ($parsed === null) {
                continue;
            }

            $count = (int) Cache::pull($key, 0);
            if ($count === 0) {
                continue;
            }

            $record = VehicleView::query()->firstOrNew([
                'vehicle_id' => $parsed['vehicle_id'],
                'hour' => $parsed['hour'],
            ]);

            $record->count = ($record->exists ? $record->count : 0) + $count;
            $record->save();

            $flushed++;
        }

        return $flushed;
    }

    /**
     * @return list<array{id: int, make: string, model: string, year: int, price: int, view_count: int}>
     */
    public function trendingVehicles(int $limit = 10): array
    {
        $since = now()->subHours(24);
        $viewCounts = $this->viewCountsLast24Hours($since);

        $ranked = collect($viewCounts)
            ->map(fn (int $viewCount, int $vehicleId) => [
                'vehicle_id' => $vehicleId,
                'view_count' => $viewCount,
            ])
            ->sort(function (array $a, array $b): int {
                if ($a['view_count'] !== $b['view_count']) {
                    return $b['view_count'] <=> $a['view_count'];
                }

                return $a['vehicle_id'] <=> $b['vehicle_id'];
            })
            ->take($limit)
            ->values();

        $vehicles = Vehicle::query()
            ->whereIn('id', $ranked->pluck('vehicle_id'))
            ->get()
            ->keyBy('id');

        return $ranked
            ->map(function (array $entry) use ($vehicles): array {
                $vehicle = $vehicles[$entry['vehicle_id']];

                return [
                    'id' => $vehicle->id,
                    'make' => $vehicle->make,
                    'model' => $vehicle->model,
                    'year' => $vehicle->year,
                    'price' => $vehicle->price,
                    'view_count' => $entry['view_count'],
                ];
            })
            ->all();
    }

    public function cacheKey(int $vehicleId, Carbon $at): string
    {
        $hour = $at->copy()->startOfHour()->format('Y-m-d-H');

        return "vehicle_views:{$vehicleId}:{$hour}";
    }

    public function pendingCacheKey(): string
    {
        return self::PENDING_KEY;
    }

    /**
     * @return array{vehicle_id: int, hour: Carbon}|null
     */
    public function parseCacheKey(string $key): ?array
    {
        if (! preg_match('/^vehicle_views:(\d+):(\d{4}-\d{2}-\d{2}-\d{1,2})$/', $key, $matches)) {
            return null;
        }

        return [
            'vehicle_id' => (int) $matches[1],
            'hour' => Carbon::createFromFormat('Y-m-d-H', $matches[2])->startOfHour(),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function viewCountsLast24Hours(Carbon $since): array
    {
        $counts = $this->databaseViewCounts($since);
        $counts = $this->applyCachedViewCounts($counts, $since);

        return array_filter($counts, fn (int $count): bool => $count > 0);
    }

    /**
     * @return array<int, int>
     */
    private function databaseViewCounts(Carbon $since): array
    {
        return VehicleView::query()
            ->where('hour', '>=', $since)
            ->select('vehicle_id', DB::raw('SUM(count) as view_count'))
            ->groupBy('vehicle_id')
            ->pluck('view_count', 'vehicle_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /**
     * @param  array<int, int>  $counts
     * @return array<int, int>
     */
    private function applyCachedViewCounts(array $counts, Carbon $since): array
    {
        foreach (Cache::get(self::PENDING_KEY, []) as $key) {
            $parsed = $this->parseCacheKey($key);
            if ($parsed === null || $parsed['hour']->lt($since)) {
                continue;
            }

            $cacheCount = (int) Cache::get($key, 0);
            if ($cacheCount === 0) {
                continue;
            }

            $counts[$parsed['vehicle_id']] = ($counts[$parsed['vehicle_id']] ?? 0) + $cacheCount;
        }

        return $counts;
    }
}
