<?php

namespace Modules\Core\Http\Requests\Item;

use App\Exceptions\PsApiException;
use App\Http\Contracts\Configuration\SettingServiceInterface;
use App\Rules\IsVendorExpired;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Modules\Core\Constants\Constants;
use Modules\Core\Http\Services\CoreFieldFilterSettingService;

class StoreItemApiRequest extends FormRequest
{
    protected $coreFieldFilterSettingService;

    public function __construct(CoreFieldFilterSettingService $coreFieldFilterSettingService, protected SettingServiceInterface $settingService)
    {
        $this->coreFieldFilterSettingService = $coreFieldFilterSettingService;

    }

    public function rules()
    {
        // Validate the custom fields
        $errors = validateForCustomField(Constants::item, $this->product_relation, $this->category_id);

        // prepare for core field validation
        $conds = prepareCoreFieldValidationConds(Constants::item);

        $coreFields = $this->coreFieldFilterSettingService->getCoreFieldsWithConds($conds);

        $setting = $this->settingService->get(env: Constants::SYSTEM_CONFIG);
        $selcted_array = json_decode($setting->setting, true);

        $validationRules = [
            [
                'fieldName' => 'id',
                'rules' => 'nullable|exists:psx_items,id',
            ],
            [
                'fieldName' => 'title',
                'rules' => 'required|min:3',
            ],
            [
                'fieldName' => 'description',
                'rules' => 'required|min:10',
            ],
            [
                'fieldName' => 'category_id',
                'rules' => 'required|exists:psx_categories,id',
            ],
            [
                'fieldName' => 'subcategory_id',
                'rules' => 'required|exists:psx_subcategories,id',
            ],
            [
                'fieldName' => 'location_city_id',
                'rules' => 'required|exists:psx_location_cities,id',
            ],
            [
                'fieldName' => 'location_township_id',
                'rules' => 'required|exists:psx_location_townships,id',
            ],
            [
                'fieldName' => 'currency_id',
                'rules' => $selcted_array['selected_price_type']['id'] == 'NORMAL_PRICE' || ! empty($this->vendor_id) ? 'required|exists:psx_currencies,id' : 'nullable',
            ],
            [
                'fieldName' => 'vendor_id',
                'rules' => ['nullable', 'exists:psx_vendors,id', new IsVendorExpired($this->vendor_id)],
            ],
            [
                'fieldName' => 'original_price',
                'rules' => 'required|max:11',
            ],
            [
                'fieldName' => 'percent',
                'rules' => 'required',
            ],
            [
                'fieldName' => 'lat',
                'rules' => 'required',
            ],
            [
                'fieldName' => 'lng',
                'rules' => 'required',
            ],
            [
                'fieldName' => 'search_tag',
                'rules' => 'required',
            ],
            [
                'fieldName' => 'ordering',
                'rules' => 'required',
            ],
            [
                'fieldName' => 'is_discount',
                'rules' => 'required',
            ],
            [
                'fieldName' => 'phone',
                'rules' => 'required',
            ],

            [
                'fieldName' => 'price',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'status',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'img_order',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'img_caption',
                'rules' => 'nullable|array',
            ],
            [
                'fieldName' => 'added_user_id',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'login_user_id',
                'rules' => 'required',
            ],
            [
                'fieldName' => 'language_symbol',
                'rules' => 'required',
            ],
            [
                'fieldName' => 'images',
                'rules' => 'required|array',
            ],
            [
                'fieldName' => 'video_icon',
                'rules' => 'required|sometimes|image',
            ],
            [
                'fieldName' => 'video',
                'rules' => 'required|sometimes|mimetypes:video/mp4',
            ],

        ];

        $validationArr = handleValidation($errors, $coreFields, $validationRules);

        // Note :
        // Currently handleValidation is overriding that not all core fields that don't have mandatory status
        // in the database, will remove required fields.
        // This is why images are also removed. So, here it re-add as required.
        // * But there is a catch,
        // For the backend, it submit the image together with item post
        // And for the frontend, it submit images and item post seperately.
        // That why we only need the required flag for the images, when it call from "backend only".
        if (! $this->expectsJson()) {
            // Replace Validations for backend for intertia
            // $validationArr['images'] = 'required|array';
            $validationArr['images'] = Rule::requiredIf(function () {
                return is_null(request()->id);
            });

            // Add the array rule for images, which always applies if 'images' is present
            $validationArr['images'] .= '|array';
        }

        return $validationArr;

    }

    public function failedValidation(Validator $validator)
    {
        if ($this->expectsJson()) {
            throw new PsApiException(
                implode("\n", Arr::flatten($validator->getMessageBag()->getMessages()))
            );
        }

        parent::failedValidation($validator);
    }

    public function attributes()
    {
        $customFieldAttributeArr = handleCFAttrForValidation(Constants::item, $this->product_relation);

        $coreFieldAttributeArr = [
            'original_price.max' => 'The original price must not be greater than 6 digits.',
        ];
        $attributeArr = array_merge($coreFieldAttributeArr, $customFieldAttributeArr);

        return $attributeArr;
    }

    public function authorize()
    {
        return true;
    }
}
