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
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campus_id')->constrained('campuses')->cascadeOnDelete();
            $table->unsignedSmallInteger('number');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 7);
            $table->text('encoded_polyline')->nullable();
            $table->unsignedSmallInteger('frequency_minutes');
            $table->time('service_starts_at');
            $table->time('service_ends_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['campus_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
