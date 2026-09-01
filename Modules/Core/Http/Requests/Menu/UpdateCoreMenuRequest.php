<?php

namespace Modules\Core\Http\Requests\Menu;

use App\Http\Contracts\Utilities\CoreFieldServiceInterface;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Constants\Constants;

class UpdateCoreMenuRequest extends FormRequest
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
        $errors = validateForCustomField(Constants::coreModule, $this->menu_relation);

        // prepare for core field validation
        $conds = prepareCoreFieldValidationConds(Constants::coreModule);
        $coreFields = $this->coreFieldService->getAll(withNoPag: true, conds: $conds);

        $validationRules = [
            [
                'fieldName' => 'module_name',
                'rules' => 'required|min:3|unique:psx_core_menus,module_name,'.$this->menu,
            ],
            [
                'fieldName' => 'core_sub_menu_group_id',
                'rules' => 'required',
            ],
            [
                'fieldName' => 'module_desc',
                'rules' => 'required|unique:psx_core_menus,module_desc,'.$this->menu,
            ],
            [
                'fieldName' => 'module_lang_key',
                'rules' => 'required|unique:psx_core_menus,module_lang_key,'.$this->menu,
            ],
            [
                'fieldName' => 'module_id',
                'rules' => 'required|unique:psx_core_menus,module_id,'.$this->menu,
            ],
            [
                'fieldName' => 'ordering',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'is_show_on_menu',
                'rules' => 'nullable',
            ],
            [
                'fieldName' => 'old_module_id',
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
        $customFieldAttributeArr = handleCFAttrForValidation(Constants::coreModule, $this->menu_relation);

        $coreFieldAttributeArr = [
            'module_name' => 'Module Name',
            'core_sub_menu_group_id' => 'Sub Menu Group',
            'module_lang_key' => 'Menu Language Key',
            'module_desc' => 'Menu Description',
            'module_id' => 'Module',
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
