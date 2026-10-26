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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('slot_id')->constrained('slots')->cascadeOnDelete();
            $table->string('status')->default('booked');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['slot_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
