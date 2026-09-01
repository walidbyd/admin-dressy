<?php

use App\Enums\CustomField\TimeFormat;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\Configuration\Setting;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $settingEnv = Constants::CUSTOM_FIELD_CONFIG;
        $setting = [
            'time_format' => ['id' => TimeFormat::HOUR_12]
        ];
        $refSelection = [
            'time_format' => [
                ['id' => TimeFormat::HOUR_12, 'value' => '12 Hour'],
                ['id' => TimeFormat::HOUR_24, 'value' => '24 Hour']
            ]
        ];
        $settingJson = json_encode($setting);
        $refSelectionJson = json_encode($refSelection);
        Setting::create([
            Setting::settingEnv => $settingEnv,
            Setting::setting => $settingJson,
            Setting::refSelection => $refSelectionJson
        ]);
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
