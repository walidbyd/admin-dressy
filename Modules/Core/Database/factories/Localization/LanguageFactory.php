<?php

namespace Modules\Core\Database\factories\Localization;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\Localization\Language;

class LanguageFactory extends Factory
{
    protected $model = Language::class;

    public function definition()
    {
        $locales = [
            'en' => 'English',
            'fr' => 'French',
            'de' => 'German',
            'es' => 'Spanish',
            'ja' => 'Japanese',
            'my' => 'Burmese',
        ];
        $symbol = $this->faker->randomElement(array_keys($locales));

        return [
            'symbol' => $symbol,
            'name' => $locales[$symbol],
            'status' => '1',
            'is_publish' => Constants::publish,
            'added_user_id' => User::factory(),
        ];
    }
}
