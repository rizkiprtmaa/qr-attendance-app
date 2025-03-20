<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('substitute_teachers', function (Blueprint $table) {
            $table->id();

            // Foreign key ke tabel subject_classes
            $table->foreignId('subject_class_id')
                ->constrained()
                ->onDelete('cascade');

            // Foreign key ke tabel users (guru pengganti)
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // Hindari duplikasi guru pengganti di kelas yang sama
            $table->unique(['subject_class_id', 'user_id']);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('substitute_teachers');
    }
};
