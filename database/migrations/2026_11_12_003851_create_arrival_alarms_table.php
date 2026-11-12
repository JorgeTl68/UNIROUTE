<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('arrival_alarms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->foreignId('stop_id')->constrained('stops')->cascadeOnDelete();
            $table->unsignedTinyInteger('minutes_before');
            $table->boolean('is_active')->default(true);
            // Último viaje que ya disparó la alarma, para avisar una sola vez por viaje.
            $table->foreignId('last_trip_id')->nullable()->constrained('trips')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'route_id', 'stop_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arrival_alarms');
    }
};
