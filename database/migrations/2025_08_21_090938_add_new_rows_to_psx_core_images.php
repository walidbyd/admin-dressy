<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\Configuration\FrontendSetting;
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
        $setting = FrontendSetting::first();
        if (isset($setting)) {
            CoreImage::create([
                CoreImage::imgType => Constants::becomeVendorImage,
                CoreImage::imgParentId => $setting->id,
                CoreImage::imgPath => 'example.png',
                CoreImage::imgWidth => '256',
                CoreImage::imgHeight => '256',
                CoreImage::ordering => 1,
                CoreImage::addedUserId => 1,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('psx_core_images', function (Blueprint $table) {
            //
        });
    }
};
