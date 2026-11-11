<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('severity');
            $table->string('workshop');
            $table->text('description');
            $table->text('resolution')->nullable();
            $table->decimal('cost', 12, 2)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['vehicle_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenance_records');
    }
};
