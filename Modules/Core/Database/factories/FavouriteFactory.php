<?php

namespace Modules\Core\Database\factories;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Entities\Item;

class FavouriteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Core\Entities\Favourite::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'item_id' => Item::all()->random(),
            'user_id' => User::all()->random(),
            'added_user_id' => 1,
            'added_date' => Carbon::now(),
        ];
    }
}
