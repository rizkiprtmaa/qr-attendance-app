<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('majors', function (Blueprint $table) {
            $table->string('badge_color')->nullable()->default('bg-gray-100 text-gray-800');
        });
    }

    public function down()
    {
        Schema::table('majors', function (Blueprint $table) {
            $table->dropColumn('badge_color');
        });
    }
};
