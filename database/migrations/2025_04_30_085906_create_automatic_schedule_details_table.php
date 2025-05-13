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
        Schema::create('automatic_schedule_details', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('automatic_schedule_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_class_id');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('jam_pelajaran');
            $table->string('session_title_template'); // Template untuk judul pertemuan (cth: "Pertemuan - %date%")
            $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('automatic_schedule_details');
    }
};
