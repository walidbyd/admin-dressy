<?php

namespace Modules\Core\Database\factories\Information;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Entities\Information\CoreAbout;

class CoreAboutFactory extends Factory
{
    protected $model = CoreAbout::class;

    public function definition()
    {
        return [
            'about_title' => $this->faker->name,
            'about_description' => $this->faker->paragraph(),
            'about_email' => $this->faker->email(),
            'about_phone' => $this->faker->phoneNumber(),
            'about_address' => $this->faker->address(),
            'about_website' => $this->faker->url(),
            'facebook' => $this->faker->url(),
            'google_plus' => $this->faker->url(),
            'instagram' => $this->faker->url(),
            'youtube' => $this->faker->url(),
            'pinterest' => $this->faker->url(),
            'twitter' => $this->faker->url(),
            'GDPR' => 'GDPR',
            'upload_point' => '50',
            'safety_tips' => $this->faker->paragraph(),
            'faq_pages' => $this->faker->paragraph(),
            'terms_and_conditions' => $this->faker->paragraph(),
            'added_user_id' => User::factory(),
        ];
    }
}
