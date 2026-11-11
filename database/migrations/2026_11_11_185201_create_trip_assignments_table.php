<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('previous_driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('previous_vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('previous_route_id')->nullable()->constrained('routes')->nullOnDelete();
            $table->foreignId('route_id')->nullable()->constrained('routes')->nullOnDelete();
            $table->string('reason', 500);
            $table->timestamps();
            $table->index(['trip_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_assignments');
    }
};
