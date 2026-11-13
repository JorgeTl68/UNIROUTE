<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('route_detours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('route_id')->constrained('routes')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('restored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('snapshot');
            $table->json('skipped_stop_ids');
            $table->timestamp('restored_at')->nullable();
            $table->timestamps();
            $table->index(['route_id', 'restored_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_detours');
    }
};
