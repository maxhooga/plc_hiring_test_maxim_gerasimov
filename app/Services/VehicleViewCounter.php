<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class VehicleViewCounter
{
    private const PENDING_KEY = 'vehicle_views:pending';

    public function recordView(int $vehicleId, ?Carbon $at = null): void
    {
        $key = $this->cacheKey($vehicleId, $at ?? now());

        Cache::increment($key);

        $pending = Cache::get(self::PENDING_KEY, []);
        $pending[] = $key;
        Cache::put(self::PENDING_KEY, array_values(array_unique($pending)));
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
}
