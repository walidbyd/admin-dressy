<?php

namespace Modules\Core\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Constants\Constants;
use Modules\Core\Http\Services\CoreFieldFilterSettingService;

class StoreSubcategoryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    protected $coreFieldFilterSettingService;

    public function __construct(CoreFieldFilterSettingService $coreFieldFilterSettingService)
    {
        $this->coreFieldFilterSettingService = $coreFieldFilterSettingService;
    }

    public function rules()
    {
        // prepare for custom field validation
        $errors = validateForCustomField(Constants::subcategory, $this->subcategory_relation);

        // prepare for core field validation
        $conds = prepareCoreFieldValidationConds(Constants::subcategory);
        $coreFields = $this->coreFieldFilterSettingService->getCoreFieldsWithConds($conds);

        $validationRules = [
            [
                'fieldName' => 'name',
                'rules' => 'required|min:3|unique:psx_subcategories',
            ],
            [
                'fieldName' => 'nameForm',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'ordering',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'category_id',
                'rules' => 'required',
            ],
            [
                'fieldName' => 'sub_cat_photo',
                'rules' => 'required|sometimes|image',
            ],
            [
                'fieldName' => 'sub_cat_icon',
                'rules' => 'required|sometimes|image',
            ],
            [
                'fieldName' => 'status',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'added_user_id',
                'rules' => 'nullable',
            ],
        ];

        // prepared saturation for core and custom field
        $validationArr = handleValidation($errors, $coreFields, $validationRules);

        return $validationArr;
    }

    public function attributes()
    {
        $customFieldAttributeArr = handleCFAttrForValidation(Constants::subcategory, $this->subcategory_relation);

        $coreFieldAttributeArr = [
            'category_id' => 'Category',
            'name' => 'Subcategory',
            'sub_cat_photo' => 'Subcategory Photo',
            'sub_cat_icon' => 'Subcategory Icon',
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
