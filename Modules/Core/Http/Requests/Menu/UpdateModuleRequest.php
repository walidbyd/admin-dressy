<?php

namespace Modules\Core\Http\Requests\Menu;

use App\Http\Contracts\Utilities\CoreFieldServiceInterface;
use App\Rules\HasRouteName;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Constants\Constants;

class UpdateModuleRequest extends FormRequest
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
        $errors = validateForCustomField(Constants::module, $this->module_relation);

        // prepare for core field validation
        $conds = prepareCoreFieldValidationConds(Constants::module);
        $coreFields = $this->coreFieldService->getAll(withNoPag: true, conds: $conds);

        $validationRules = [
            [
                'fieldName' => 'title',
                'rules' => 'required|min:3|unique:psx_modules,title,'.$this->module,
            ],
            [
                'fieldName' => 'route_name',
                'rules' => ['required', new HasRouteName, 'unique:psx_modules,route_name,'.$this->module],
            ],
            [
                'fieldName' => 'lang_key',
                'rules' => 'required',
            ],
            [
                'fieldName' => 'is_not_from_sidebar',
                'rules' => 'nullable',
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
        $customFieldAttributeArr = handleCFAttrForValidation(Constants::module, $this->module_relation);

        $coreFieldAttributeArr = [
            'route_name' => 'Route Name',
            'lang_key' => 'Language Key',
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
