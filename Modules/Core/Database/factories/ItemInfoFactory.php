<?php

namespace Modules\Core\Database\factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Entities\Item\Item;
use Modules\Core\Entities\ItemInfo;
use Modules\Core\Entities\Utilities\CustomField;

class ItemInfoFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ItemInfo::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $customField = CustomField::factory()->make();

        return [
            ItemInfo::itemId => Item::factory(),
            ItemInfo::coreKeysId => $customField->{CustomField::coreKeysId},
            ItemInfo::value => $this->faker->randomLetter(),
            ItemInfo::uiTypeId => $customField->{CustomField::uiTypeId},
            ItemInfo::addedUserId => User::factory(),
        ];
    }
}
