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
        Schema::create('substitution_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Guru yang digantikan
            $table->foreignId('substitute_teacher_id')->constrained('users')->onDelete('cascade'); // Guru pengganti
            $table->foreignId('subject_class_id')->constrained()->onDelete('cascade'); // Kelas yang akan digantikan
            $table->date('start_date');
            $table->date('end_date')->nullable(); // Null jika hanya 1 hari
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->text('reason')->nullable(); // Alasan penggantian
            $table->text('admin_notes')->nullable(); // Catatan dari admin
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('substitution_requests');
    }
};
