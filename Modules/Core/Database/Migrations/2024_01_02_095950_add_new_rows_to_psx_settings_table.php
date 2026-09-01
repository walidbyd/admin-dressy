<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
        $setting = new Setting;
        $setting->setting_env = 'vendor_subscription_config';
        $setting->save();

        $selected_subscription_setting = [];
        $reference_setting = [];

        $selected_setting = [
            [
                'id' => 'SUBSCRIPTION_PLANS',
            ],
        ];

        $ref_setting = [
            [
                'id' => 'FREE',
                'value' => 'Free',
            ],
            [
                'id' => 'SUBSCRIPTION_PLANS',
                'value' => 'Subscription Plans',
            ],
        ];

        $selected_subscription_setting['subscription_plan'] = $selected_setting;
        $reference_setting['subscription_plans'] = $ref_setting;

        $setting->setting = $selected_subscription_setting;
        $setting->ref_selection = $reference_setting;

        $setting->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('psx_settings', function (Blueprint $table) {});
    }
};
