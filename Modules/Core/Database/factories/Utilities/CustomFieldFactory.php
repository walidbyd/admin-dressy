<?php

namespace Modules\Core\Database\factories\Utilities;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\Utilities\CustomField;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class CustomFieldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = CustomField::class;

    public function definition()
    {
        $coreKeysId = 'loc'.str_pad($this->faker->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT);
        $uitTypes = [
            Constants::dropDownUi,
            Constants::textUi,
            Constants::radioUi,
            Constants::checkBoxUi,
            Constants::dateTimeUi,
            Constants::textAreaUi,
            Constants::numberUi,
            Constants::multiSelectUi,
            Constants::imageUi,
            Constants::timeOnlyUi,
            Constants::dateOnlyUi,
        ];
        $user = User::factory()->create();

        return [
            'name' => "{$coreKeysId}_name",
            'placeholder' => "{$coreKeysId}_placeholder",
            'ui_type_id' => $this->faker->randomElement($uitTypes),
            'core_keys_id' => $coreKeysId,
            'mandatory' => 0,
            'is_show_sorting' => 1,
            'is_show_in_filter' => 1,
            'ordering' => 1,
            'enable' => 1,
            'is_delete' => 0,
            'module_name' => 'loc',
            'data_type' => 'String',
            'table_id' => 1,
            'project_id' => 1,
            'project_name' => 'Testing',
            'base_module_name' => 16,
            'is_include_in_hideshow' => 1,
            'is_show' => 1,
            'is_core_field' => 0,
            'permission_for_enable_disable' => 0,
            'permission_for_delete' => 0,
            'permission_for_mandatory' => 0,
            'category_id' => null,
            'added_date' => '',
            'added_user_id' => $user->{User::id},
            'updated_date' => '',
            'updated_user_id' => $user->{User::id},
            'updated_flag' => '',
        ];
    }
}
