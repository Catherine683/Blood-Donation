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
        Schema::create('drives', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('venue');
            $table->string('address')->nullable();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index('status');
            $table->index('starts_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drives');
    }
};
