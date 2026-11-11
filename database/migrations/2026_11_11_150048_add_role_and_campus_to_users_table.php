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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student')->index()->after('email');
            $table->string('student_id')->nullable()->unique()->after('role');
            $table->foreignId('campus_id')->nullable()->after('student_id')
                ->constrained('campuses')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('campus_id');
            $table->dropUnique(['student_id']);
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'student_id']);
        });
    }
};
