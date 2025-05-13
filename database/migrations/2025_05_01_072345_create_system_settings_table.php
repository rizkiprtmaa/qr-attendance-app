<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->text('description')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, json
            $table->boolean('is_public')->default(false);
        });

        // Insert default settings
        DB::table('system_settings')->insert([
            [
                'key' => 'attendance_auto_absent_datang_time',
                'value' => '08:30',
                'description' => 'Waktu otomatis siswa dianggap tidak hadir pada kedatangan (QR)',
                'type' => 'string',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'attendance_auto_absent_pulang_time',
                'value' => '14:30',
                'description' => 'Waktu otomatis siswa dianggap tidak hadir pada kepulangan (QR)',
                'type' => 'string',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'whatsapp_gateway_enabled',
                'value' => 'true',
                'description' => 'Status aktif WhatsApp Gateway',
                'type' => 'boolean',
                'is_public' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
