<?php

namespace App\Services;

use App\Models\VehicleView;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

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
}
