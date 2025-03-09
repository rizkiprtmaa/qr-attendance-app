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
        Schema::table('users', function (Blueprint $table) {
            $table->string('qr_token')->nullable()->unique()->after('email');
            $table->string('qr_code_path')->nullable()->after('qr_token');
            $table->timestamp('last_login_at')->nullable()->after('qr_code_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['qr_token', 'qr_code_path', 'last_login_at']);
        });
    }
};
