<?php

namespace Modules\Core\Tests\Feature\Information;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AboutApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_the_about_api_returns_a_successful_response_and_structure(): void
    {

        $endpoint = '/api/v1.0/about';
        $queryParams = [
            'login_user_id' => 1,
            'language_symbol' => 'en',
        ];

        $response = $this->getJson($endpoint.'?'.http_build_query($queryParams));

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'id',
            'about_title',
            'about_description',
            'about_email',
            'about_phone',
            'about_address',
            'about_website',
            'facebook',
            'google_plus',
            'instagram',
            'youtube',
            'pinterest',
            'twitter',
            'GDPR',
            'upload_point',
            'safety_tips',
            'faq_pages',
            'terms_and_conditions',
            'default_photo' => [
                'img_id',
                'img_parent_id',
                'img_type',
                'img_path',
                'img_width',
                'img_height',
                'img_desc',
                'ordering',
                'added_date_str',
            ],
            'privacy_policy',
            'data_deletion_policy',
        ]);

    }
}
