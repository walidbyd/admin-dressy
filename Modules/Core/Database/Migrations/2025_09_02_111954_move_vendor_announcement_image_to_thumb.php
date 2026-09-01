<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Core\Constants\Constants;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $source = public_path('images/assets/vendorAnnouncement.png');
        $destinationDir = public_path('storage/'.Constants::folderPath.'/thumbnail');
        $destination = $destinationDir.'/vendorAnnouncement.png';
        if (file_exists($source) && ! file_exists($destination)) {
            copy($source, $destination);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
};
