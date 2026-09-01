<?php

namespace Modules\Core\Database\factories;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Entities\Category\Category;
use Modules\Core\Entities\Category\Subcategory;
use Modules\Core\Entities\Financial\ItemCurrency;
use Modules\Core\Entities\Item\Item;
use Modules\Core\Entities\Location\LocationCity;
use Modules\Core\Entities\Location\LocationTownship;

class ItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Item::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $price = $this->faker->numberBetween($min = 10, $max = 9000);
        $items = [
            'title' => ucwords($this->faker->word),
            'category_id' => Category::factory(),
            'subcategory_id' => null, // Subcategory::where('category_id', $category->id)->get()->random(),
            'currency_id' => ItemCurrency::factory(),
            'shop_id' => null,
            'location_city_id' => LocationCity::factory(),
            'location_township_id' => null, // LocationTownship::where('location_city_id', $city->id)->get()->random(),
            'price' => $price,
            'dynamic_link' => null,
            'original_price' => $price,
            'description' => $this->faker->paragraph,
            'search_tag' => $this->faker->word,
            'lat' => $this->faker->latitude($min = -90, $max = 90),
            'lng' => $this->faker->longitude($min = -180, $max = 180),
            'status' => 1,
            'ordering' => $this->faker->numberBetween($min = 1, $max = 10),
            'is_available' => $this->faker->numberBetween($min = 0, $max = 1),
            'item_touch_count' => $this->faker->numberBetween($min = 1, $max = 300),
            'favourite_count' => $this->faker->numberBetween($min = 1, $max = 300),
            'overall_rating' => $this->faker->numberBetween($min = 0, $max = 5),
            'added_user_id' => User::factory(),
            'added_date' => Carbon::now(),
        ];

        return $items;
    }
}
