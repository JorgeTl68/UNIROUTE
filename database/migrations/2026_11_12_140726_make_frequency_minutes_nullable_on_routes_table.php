<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Las rutas de la UTC salen una vez por turno: no todas tienen una frecuencia
 * fija, así que la frecuencia pasa a ser opcional.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->unsignedSmallInteger('frequency_minutes')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('routes')->whereNull('frequency_minutes')->update(['frequency_minutes' => 60]);

        Schema::table('routes', function (Blueprint $table) {
            $table->unsignedSmallInteger('frequency_minutes')->nullable(false)->change();
        });
    }
};
