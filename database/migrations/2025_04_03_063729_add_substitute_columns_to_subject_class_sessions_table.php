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
        Schema::table('subject_class_sessions', function (Blueprint $table) {
            $table->foreignId('created_by_substitute')->nullable();
            $table->foreignId('substitution_request_id')->nullable();
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('subject_class_sessions', function (Blueprint $table) {
            $table->dropColumn(['created_by_substitute', 'substitution_request_id', 'notes']);
        });
    }
};
