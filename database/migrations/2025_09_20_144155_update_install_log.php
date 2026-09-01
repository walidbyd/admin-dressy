<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Installer\Helpers\InstalledFileManager;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $installManager = new InstalledFileManager;
        $installedLogFile = storage_path('installed');
        if (file_exists($installedLogFile)) {
            $installManager->update();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
