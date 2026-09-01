<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('psx_builder_app_info_caches', function (Blueprint $table) {
            $table->text('cached_data')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('psx_builder_app_info_caches', function (Blueprint $table) {
            $table->string('cached_data', 255)->change();
        });
    }
};
