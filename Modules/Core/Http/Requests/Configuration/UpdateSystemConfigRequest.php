<?php

namespace Modules\Core\Http\Requests\Configuration;

use App\Enums\CustomField\TimeFormat;
use App\Http\Contracts\Utilities\CoreFieldServiceInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Constants\Constants;

class UpdateSystemConfigRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function __construct(protected CoreFieldServiceInterface $coreFieldService) {}

    public function rules()
    {
        // System config
        $sysErrors = validateForCustomField(Constants::systemConfig, $this->system_config_relation);

        $conds = prepareCoreFieldValidationConds(Constants::systemConfig);
        $sysCoreFields = $this->coreFieldService->getAll(withNoPag: true, conds: $conds);

        $sysValidationRules = [
            [
                'fieldName' => 'sysForm.lat',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'sysForm.lng',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'sysForm.is_approved_enable',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'sysForm.is_sub_location',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'sysForm.is_thumb2x_3x_generate',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'sysForm.is_sub_subscription',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'sysForm.is_paid_app',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'sysForm.is_promote_enable',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'sysForm.free_ad_post_count',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'sysForm.is_block_user',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'sysForm.max_img_upload_of_item',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'sysForm.ad_type',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'sysForm.promo_cell_interval_no',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'sysForm.one_day_per_price',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'sysForm.selected_price_type',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'sysForm.selected_chat_type',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'sysForm.soldout_feature_setting',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'sysForm.hide_price_setting',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'sysForm.display_ads_id',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'sysForm.ads_client',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'sysForm.is_display_google_adsense',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'sysForm.ads_txt_file',
                'rules' => 'nullable|file|mimetypes:text/plain',
            ],
        ];

        // Mobile setting
        $mbErrors = validateForCustomField(Constants::mobileSetting, $this->mobile_setting_relation);

        $mbConds = prepareCoreFieldValidationConds(Constants::mobileSetting);
        $mbCoreFields = $this->coreFieldService->getAll(withNoPag: true, conds: $mbConds);

        $mbValidationRules = [
            [
                'fieldName' => 'form.promote_first_choice_day',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.promote_second_choice_day',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.promote_third_choice_day',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.promote_fourth_choice_day',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.profile_image_size',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.upload_image_size',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.chat_image_size',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.blue_mark_size',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.show_facebook_login',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.show_phone_login',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.show_google_login',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.show_apple_login',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.is_force_login',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.default_loading_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.category_loading_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.recent_item_loading_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.popular_item_loading_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.discount_item_loading_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.featured_item_loading_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.block_slider_loading_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.follower_item_loading_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.block_item_loading_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.recent_search_keyword_limit',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.default_razor_currency',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.is_razor_support_multi_currency',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.is_show_subcategory',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.price_format',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.mile',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.is_show_discount',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.is_use_thumbnail_as_placeholder',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.no_filter_with_location_on_map',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.is_show_owner_info',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.is_show_item_video',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.is_demo_for_payment',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.phone_list_count',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'form.video_duration',
                'rules' => 'nullable',
            ],
        ];

        $customFieldConfigValidationRules = [
            [
                'fieldName' => 'customFieldForm.time_format',
                'rules' => [
                    'nullable',
                    Rule::in(TimeFormat::values())
                ],
            ],
        ];

        $errors = array_merge($sysErrors, $mbErrors);
        $coreFields = $sysCoreFields->merge($mbCoreFields);
        $validationRules = array_merge($sysValidationRules, $mbValidationRules, $customFieldConfigValidationRules);

        // prepared saturation for core and custom field
        $validationArr = handleValidation($errors, $coreFields, $validationRules);

        return $validationArr;

    }

    public function attributes()
    {
        $customFieldAttributeArr = handleCFAttrForValidation(Constants::systemConfig, $this->system_config_relation);

        $coreFieldAttributeArr = [
            'sysForm.ads_txt_file' => 'Ads.txt file',
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
