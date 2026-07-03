<?php

namespace App\Console\Commands;

use App\Services\VehicleViewCounter;
use Illuminate\Console\Command;

class FlushVehicleViewsCommand extends Command
{
    protected $signature = 'vehicle-views:flush';

    protected $description = 'Flush cached vehicle view counts into the database';

    public function handle(VehicleViewCounter $viewCounter): int
    {
        $flushed = $viewCounter->flush();

        $this->components->info("Flushed {$flushed} cached view bucket(s) to the database.");

        return self::SUCCESS;
    }
}
