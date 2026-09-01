<?php

namespace Modules\Core\Database\factories;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Entities\Item;
use Modules\Core\Entities\Touch;

class TouchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Touch::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'type_id' => Item::all()->random(),
            'user_id' => User::all()->random(),
            'shop_id' => '1',
            'type_name' => 'Item',
            'added_user_id' => 1,
            'added_date' => Carbon::now(),
        ];
    }
}
