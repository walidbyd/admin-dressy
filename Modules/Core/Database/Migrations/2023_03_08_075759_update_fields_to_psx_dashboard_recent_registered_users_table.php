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
        Schema::table('psx_dashboard_recent_registered_users', function (Blueprint $table) {
            $table->string('overall_rating')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('psx_dashboard_recent_registered_users', function (Blueprint $table) {
            $table->string('overall_rating')->nullable(false)->change();
        });
    }
};
