<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Services\VehicleViewCounter;
use Illuminate\Http\JsonResponse;

class VehicleController extends Controller
{
    public function __construct(private VehicleViewCounter $viewCounter) {}

    public function show(Vehicle $vehicle): JsonResponse
    {
        $this->viewCounter->recordView($vehicle->id);

        return response()->json($vehicle);
    }

    public function trending(): JsonResponse
    {
        return response()->json($this->viewCounter->trendingVehicles());
    }
}
