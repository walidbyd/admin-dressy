<?php

use Illuminate\Database\Migrations\Migration;
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
        Schema::dropIfExists('psx_api_call_settings');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
};
