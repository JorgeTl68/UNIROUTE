
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arrival_alarms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('route_id')
                ->constrained('routes')
                ->cascadeOnDelete();

            $table->foreignId('stop_id')
                ->constrained('stops')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('minutes_before')->default(5);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Una sola alarma por estudiante, ruta y parada.
            $table->unique(
                ['user_id', 'route_id', 'stop_id'],
                'arrival_alarms_user_route_stop_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arrival_alarms');
    }
};
