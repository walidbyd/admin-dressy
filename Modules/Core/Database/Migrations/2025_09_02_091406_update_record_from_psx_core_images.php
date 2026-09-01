<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\CoreImage;

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
        $destinationDir = public_path('storage/'.Constants::folderPath.'/uploads');
        $destination = $destinationDir.'/vendorAnnouncement.png';
        if (file_exists($source) && ! file_exists($destination)) {
            copy($source, $destination);
        }
        [$width, $height] = getimagesize($destination);

        CoreImage::where([CoreImage::imgType => Constants::becomeVendorImage])->update([
            CoreImage::imgPath => 'vendorAnnouncement.png',
            CoreImage::imgWidth => $width,
            CoreImage::imgHeight => $height,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
};
