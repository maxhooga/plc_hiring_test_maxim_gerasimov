<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reshape vehicle_views from one row per view to hourly aggregates.
 *
 * The starter migration stores every single page view as its own row. At ~50 req/s
 * on GET /api/vehicles/{id} that would mean millions of INSERTs per day and heavy
 * trending queries. This migration replaces viewed_at with (hour, count) buckets
 * so increments can be upserted per vehicle per hour instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_views', function (Blueprint $table) {
            $table->dropIndex(['viewed_at']);
            $table->dropColumn('viewed_at');
            $table->timestamp('hour');
            $table->unsignedInteger('count')->default(0);
            $table->unique(['vehicle_id', 'hour']);
            $table->index('hour');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_views', function (Blueprint $table) {
            $table->dropUnique(['vehicle_id', 'hour']);
            $table->dropIndex(['hour']);
            $table->dropColumn(['hour', 'count']);
            $table->timestamp('viewed_at')->useCurrent()->index();
        });
    }
};
