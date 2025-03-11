<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('attendance_date');
            $table->enum('type', ['datang', 'pulang']);
            $table->enum('status', ['hadir', 'terlambat', 'pulang_cepat', 'izin'])->default('hadir');
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable(); // Tambahkan kolom check_out_time
            $table->timestamps();
            $table->unique(['user_id', 'attendance_date', 'type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendances');
    }
};
