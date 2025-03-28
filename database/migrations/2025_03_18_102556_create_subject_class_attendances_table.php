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
        Schema::create('subject_class_attendances', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('subject_class_session_id');
            $table->foreignId('student_id');
            $table->enum('status', ['hadir', 'tidak_hadir', 'sakit', 'izin'])->default('tidak_hadir');
            $table->datetime('check_in_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_class_attendances');
    }
};
