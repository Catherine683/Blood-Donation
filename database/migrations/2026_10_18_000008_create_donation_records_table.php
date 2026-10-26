<?php

declare(strict_types=1);

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
        Schema::create('donation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('drive_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('outcome', 20); // 'donated', 'deferred'
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('recorded_at');
            $table->string('staff_note', 255)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'drive_id']);
            $table->index('user_id');
            $table->index('drive_id');
            $table->index('recorded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donation_records');
    }
};
