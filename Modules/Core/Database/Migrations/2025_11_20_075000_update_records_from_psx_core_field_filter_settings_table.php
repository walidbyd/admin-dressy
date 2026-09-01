<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\Utilities\CoreField;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $fieldNames = [
            'phone',
            'percent',
            'item_image',
            'item_video',
            'status',
            'subcategory_id@@name',
            'location_township_id@@name'
        ];

        foreach ($fieldNames as $fieldName) {
            $coreField = CoreField::where([
                CoreField::moduleName => Constants::item,
                CoreField::fieldName => $fieldName
            ])->first();

            if ($coreField) $coreField->update([CoreField::permissionForMandatory => 1]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
};
