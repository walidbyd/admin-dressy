<?php

namespace Modules\Core\Http\Requests\Configuration;

use App\Http\Contracts\Utilities\CoreFieldServiceInterface;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Constants\Constants;

class StoreFrontendSettingRequest extends FormRequest
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
        $errors = validateForCustomField(Constants::frontendSetting, $this->frontend_setting_relation);

        // prepare for core field validation
        $conds = prepareCoreFieldValidationConds(Constants::frontendSetting);
        $coreFields = $this->coreFieldService->getAll(withNoPag: true, conds: $conds);

        $validationRules = [
            [
                'fieldName' => 'map_key',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'is_enable_video_setting',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'show_user_profile',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'no_filter_with_location_on_map',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'price_format',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'enable_notification',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'fcm_server_key',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'firebase_web_push_key_pair',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'firebase_config',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'ad_client',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'ad_slot',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'is_ads_on',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'copyright',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'google_playstore_url',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'google_setting',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'app_store_url',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'app_store_setting',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'banner_src',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'google_map',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'open_street_map',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'mile',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'default_language',
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
                'fieldName' => 'gps_enable',
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
                'fieldName' => 'frontendColors',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'frontend_version_no',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'frontend_logo',
                'rules' => 'nullable|sometimes|image',
            ],
            [
                'fieldName' => 'frontend_icon',
                'rules' => 'nullable|sometimes|image',
            ],
            [
                'fieldName' => 'frontend_banner',
                'rules' => 'nullable|sometimes|image',
            ],
            [
                'fieldName' => 'frontend_meta_title',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'frontend_meta_description',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'frontend_meta_image',
                'rules' => 'nullable|sometimes|image',
            ],
            [
                'fieldName' => 'app_branding_image',
                'rules' => 'nullable|sometimes|image',
            ],
            [
                'fieldName' => 'facebook_url',
                'rules' => 'nullable|url',
            ],
            [
                'fieldName' => 'youtube_url',
                'rules' => 'nullable|url',
            ],
            [
                'fieldName' => 'twitter_url',
                'rules' => 'nullable|url',
            ],
            [
                'fieldName' => 'linkedin_url',
                'rules' => 'nullable|url',
            ],
            [
                'fieldName' => 'instagram_url',
                'rules' => 'nullable|url',
            ],
            [
                'fieldName' => 'pinterest_url',
                'rules' => 'nullable|url',
            ],
        ];

        // prepared saturation for core and custom field
        $validationArr = handleValidation($errors, $coreFields, $validationRules);

        return $validationArr;

    }

    public function attributes()
    {
        $customFieldAttributeArr = handleCFAttrForValidation(Constants::frontendSetting, $this->frontend_setting_relation);

        $coreFieldAttributeArr = [
            'frontend_logo' => 'Frontend Logo',
            'frontend_icon' => 'Frontend Icon',
            'frontend_banner' => 'Frontend Banner',
            'frontend_meta_image' => 'Frontend Meta Image',
            'app_branding_image' => 'App Branding Image',
        ];
        $attributeArr = array_merge($coreFieldAttributeArr, $customFieldAttributeArr);

        return $attributeArr;
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
