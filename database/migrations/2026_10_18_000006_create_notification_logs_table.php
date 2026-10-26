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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->string('channel')->default('mail');
            $table->string('status')->default('queued');
            $table->timestamp('sent_at')->nullable();
            $table->nullableMorphs('related');
            $table->string('error', 500)->nullable();
            $table->string('dedupe_key')->nullable()->unique();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
