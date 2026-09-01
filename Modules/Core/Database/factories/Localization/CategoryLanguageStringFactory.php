<?php

namespace Modules\Core\Database\factories\Localization;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Entities\Category\Category;
use Modules\Core\Entities\Localization\CategoryLanguageString;
use Modules\Core\Entities\Localization\Language;

class CategoryLanguageStringFactory extends Factory
{
    protected $model = CategoryLanguageString::class;

    public function definition()
    {
        return [
            CategoryLanguageString::categoryId => Category::factory(),
            CategoryLanguageString::languageId => Language::factory(),
            CategoryLanguageString::key => 'test_key',
            CategoryLanguageString::value => 'Test Value',
            CategoryLanguageString::addedUserId => User::factory(),
        ];
    }
}
