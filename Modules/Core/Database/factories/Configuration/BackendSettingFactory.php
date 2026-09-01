<?php

namespace Modules\Core\Database\factories\Configuration;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Entities\Configuration\BackendSetting;

class BackendSettingFactory extends Factory
{
    protected $model = BackendSetting::class;

    public function definition()
    {
        return [
            'sender_name' => 'Test Sender',
            'sender_email' => 'test@gmail.com',
            'receive_email' => 'test@gmail.com',
            'fcm_api_key' => '000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000',
            'app_token' => '00000000000000000000000000000000000000000000',
            'topics' => 'broadcast',
            'topics_fe' => 'fe_broadcast',
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => '465',
            'smtp_user' => 'teamps.test@gmail.com',
            'smtp_pass' => 'mrfk znjv qgtr tdhr',
            'smtp_encryption' => 'ssl',
            'email_verification_enabled' => '0',
            'user_social_info_override' => '1',
            'landscape_width' => '1000',
            'potrait_height' => '1000',
            'square_height' => '1000',
            'landscape_thumb2x_width' => '400',
            'potrait_thumb2x_height' => '800',
            'square_thumb2x_height' => '400',
            'landscape_thumb3x_width' => '720',
            'potrait_thumb3x_height' => '800',
            'square_thumb3x_height' => '720',
            'search_item_limit' => '5',
            'search_user_limit' => '5',
            'search_category_limit' => '5',
            'dyn_link_key' => 'AIzaSyBZXIFW9R_FCqHzfjNYdUE5Tb0GiNu-Jog',
            'dyn_link_url' => 'https://firebasedynamiclinks.googleapis.com/v1/shortLinks?key=',
            'dyn_link_package_name' => 'com.panaceasoft.psxmpc',
            'dyn_link_domain' => 'psx.page.link',
            'dyn_link_deep_url' => 'https://www.products.panacea-soft.co/psx-mpc-dev2',
            'ios_boundle_id' => 'com.panaceasoft.psxmpc',
            'ios_appstore_id' => '0000000000',
            'backend_version_no' => '1.5.5',
            'slow_moving_item_limit' => '5',
            'date_format' => 'MM-DD-YYYY',
            'map_key' => '000000000000000000000000000000000000000',
            'upload_setting' => 'all',
            'is_watermask' => '1',
            'watermask_image_size' => '1000',
            'font_size' => '5',
            'position' => 'bottom-right',
            'padding' => '10',
            'watermask_color' => '',
            'added_user_id' => User::factory(),
            'opacity' => '100',
            'is_google_map' => '0',
            'is_open_street_map' => '1',
            'watermask_title' => '',
            'watermask_angle' => '10',
            'fe_setting' => '1',
            'vendor_setting' => '1',
        ];
    }
}
