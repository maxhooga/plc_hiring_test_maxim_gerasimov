<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'vehicle_id',
        'hour',
        'count',
    ];

    protected function casts(): array
    {
        return [
            'hour' => 'datetime',
            'count' => 'integer',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
