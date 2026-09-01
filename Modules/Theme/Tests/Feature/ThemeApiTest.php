<?php

namespace Modules\Theme\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ThemeApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_the_about_api_returns_a_successful_response_and_structure(): void
    {

        $endpoint = '/api/v1.0/theme/get_all_theme_info_for_mobile';

        $response = $this->getJson($endpoint);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'theme_info' => [
                'theme_id',
                'theme_name',
                'screens' => [],
            ],
        ]);
    }
}
