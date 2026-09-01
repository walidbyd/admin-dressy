<?php

namespace Modules\Core\Database\factories\Financial;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Entities\Financial\ItemCurrency;

class ItemCurrencyFactory extends Factory
{
    protected $model = ItemCurrency::class;

    public function definition()
    {
        return [
            'currency_symbol' => $this->faker->randomElement(['$', '€', '£', '¥']),
            'currency_short_form' => $this->faker->currencyCode,
            'status' => 1,
            'is_default' => 0,
            'added_date' => Carbon::now(),
            'added_user_id' => 1,
            'updated_date' => Carbon::now(),
            'updated_user_id' => 1,
            'updated_flag' => 0,
        ];
    }
}
