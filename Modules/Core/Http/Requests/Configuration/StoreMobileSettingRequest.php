<?php

namespace Modules\Core\Http\Requests\Configuration;

use App\Http\Contracts\Utilities\CoreFieldServiceInterface;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Constants\Constants;

class StoreMobileSettingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function __construct(protected CoreFieldServiceInterface $coreFieldService) {}

    public function rules()
    {
        // prepare for custom field validation
        $errors = validateForCustomField(Constants::mobileSetting, $this->mobile_setting_relation);

        // prepare for core field validation
        $conds = prepareCoreFieldValidationConds(Constants::mobileSetting);
        $coreFields = $this->coreFieldService->getAll(withNoPag: true, conds: $conds);

        $validationRules = [
            [
                'fieldName' => 'apple_appstore_url',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'ios_appstore_id',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'google_playstore_url',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'is_show_admob',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'is_show_item_video',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'fb_key',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'date_format',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'price_format',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'default_razor_currency',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'is_razor_support_multi_currency',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'is_show_subcategory',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'is_show_discount',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'color_change_code',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'show_phone_login',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'show_google_login',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'show_apple_login',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'show_facebook_login',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'is_use_thumbnail_as_placeholder',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'is_use_google_map',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'item_detail_view_count_for_ads',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'is_show_ads_in_item_detail',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'after_item_count_admob_once',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'blue_mark_size',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'block_item_loading_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'follower_item_loading_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'block_slider_loading_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'featured_item_loading_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'popular_item_loading_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'recent_item_loading_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'category_loading_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'default_loading_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'discount_item_loading_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'mile',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'video_duration',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'is_show_owner_info',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'is_force_login',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'is_language_config',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'no_filter_with_location_on_map',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'chat_image_size',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'profile_image_size',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'upload_image_size',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'promote_first_choice_day',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'promote_second_choice_day',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'promote_third_choice_day',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'promote_fourth_choice_day',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'default_language',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'selected_language',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'lat',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'lng',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'collection_item_loading_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'shop_loading_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'show_main_menu',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'show_special_collections',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'show_featured_items',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'show_best_choice_slider',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'default_flutter_wave_currency',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'default_order_time',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'trending_item_loading_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'version_no',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'version_force_update',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'version_title',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'version_message',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'version_need_clear_data',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'android_admob_banner_ad_unit_id',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'android_admob_native_unit_id',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'andorid_admob_interstitial_ad_unit_id',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'ios_admob_banner_ad_unit_id',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'ios_admob_native_ad_unit_id',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'ios_admob_interstitial_ad_unit_id',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'recent_search_keyword_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'data_config_data_source_type',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'data_config_day',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'is_slider_auto_play',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'is_demo_for_payment',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'auto_play_interval',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'loading_shimmer_item_count',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'phone_list_count',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'deli_boy_version_no',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'deli_boy_version_force_update',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'deli_boy_version_title',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'deli_boy_version_message',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'deli_boy_version_need_clear_data',
                'rules' => 'nullable',
            ],
        ];

        // prepared saturation for core and custom field
        $validationArr = handleValidation($errors, $coreFields, $validationRules);

        return $validationArr;

    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
