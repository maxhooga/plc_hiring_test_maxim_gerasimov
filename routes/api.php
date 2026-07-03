<?php

use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::get('/vehicles', [VehicleController::class, 'index']);
Route::get('/vehicles/trending', [VehicleController::class, 'trending']);
Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show']);
