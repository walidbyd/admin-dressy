<?php

namespace Modules\Core\Http\Requests\Configuration;

use App\Http\Contracts\Utilities\CoreFieldServiceInterface;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Constants\Constants;

class UpdateBackendSettingRequest extends FormRequest
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
        $errors = validateForCustomField(Constants::backendSetting, $this->backend_setting_relation);

        // prepare for core field validation
        $conds = prepareCoreFieldValidationConds(Constants::backendSetting);
        $coreFields = $this->coreFieldService->getAll(withNoPag: true, conds: $conds);

        $validationRules = [
            [
                'fieldName' => 'sender_name',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'sender_email',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'receive_email',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'fcm_api_key',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'map_key',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'app_token',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'topics',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'topics_fe',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'smtp_host',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'smtp_port',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'smtp_user',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'smtp_pass',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'smtp_encryption',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'email_verification_enabled',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'user_social_info_override',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'landscape_width',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'potrait_height',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'square_height',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'landscape_thumb_width',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'potrait_thumb_height',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'square_thumb_height',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'landscape_thumb2x_width',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'potrait_thumb2x_height',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'square_thumb2x_height',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'landscape_thumb3x_width',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'potrait_thumb3x_height',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'square_thumb3x_height',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'default_dynamic_link',
                'rules' => 'required',
            ],
            [
                'fieldName' => 'scheme_name',
                'rules' => 'required',
            ],
            [
                'fieldName' => 'android_package',
                'rules' => 'required',
            ],
            [
                'fieldName' => 'apple_id',
                'rules' => 'required',
            ],
            [
                'fieldName' => 'dyn_link_key',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'dyn_link_url',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'dyn_link_package_name',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'dyn_link_domain',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'dyn_link_deep_url',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'ios_boundle_id',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'ios_appstore_id',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'backend_version_no',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'slow_moving_item_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'search_item_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'search_user_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'search_category_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'date_format',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'backend_logo',
                'rules' => 'nullable|sometimes|image',
            ],
            [
                'fieldName' => 'fav_icon',
                'rules' => 'nullable|sometimes|image',
            ],
            [
                'fieldName' => 'backend_login_image',
                'rules' => 'nullable|sometimes|image',
            ],
            [
                'fieldName' => 'backend_water_mask_image',
                'rules' => 'nullable|sometimes|image',
            ],
            [
                'fieldName' => 'water_mask_background',
                'rules' => 'nullable|sometimes|image',
            ],
            [
                'fieldName' => 'watermask_image_size',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'font_size',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'position',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'upload_setting',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'opacity',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'commonColor',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'watermask_title',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'is_watermask',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'watermask_angle',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'padding',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'is_google_map',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'is_open_street_map',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'fe_setting',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'vendor_setting',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'firebasePrivateKeyJsonFile',
                'rules' => 'nullable|file|mimetypes:application/json',
            ],
        ];

        // prepared saturation for core and custom field
        $validationArr = handleValidation($errors, $coreFields, $validationRules);

        return $validationArr;

    }

    public function attributes()
    {
        $customFieldAttributeArr = handleCFAttrForValidation(Constants::backendSetting, $this->backend_setting_relation);

        $coreFieldAttributeArr = [
            'backend_logo' => 'Backend Logo',
            'fav_icon' => 'Fav Icon',
            'backend_login_image' => 'Backend Login Image',
            'backend_water_mask_image' => 'Backend Water Mask Image',
            'water_mask_background' => 'Water Mask Background',
            'is_watermask' => 'Is Watermask',
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
