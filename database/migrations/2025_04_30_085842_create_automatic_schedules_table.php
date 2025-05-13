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
        Schema::create('automatic_schedules', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('day_of_week'); // Senin, Selasa, dst (bisa juga pakai integer 1-7)
            $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automatic_schedules');
    }
};
