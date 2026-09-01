<?php

namespace Modules\Core\Database\factories\Category;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Entities\Category\Category;
use Modules\Core\Entities\Category\Subcategory;

class SubcategoryFactory extends Factory
{
    protected $model = Subcategory::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'category_id' => Category::factory(),
            'ordering' => 1,
            'status' => 0,
            'added_user_id' => 1,
        ];
    }
}
