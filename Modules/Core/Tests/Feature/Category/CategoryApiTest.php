<?php

namespace Modules\Core\Tests\Feature\Category;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Entities\Category\Category;
use Modules\Core\Entities\Localization\CategoryLanguageString;
use Modules\Core\Entities\Localization\Language;
use Tests\TestCase;

class CategoryApiTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_category_search_api_returns_no_content_response_when_offest_is_zero_and_there_is_no_data(): void
    {
        $this->actingAs($this->user);

        $language = Language::factory()->create([
            'symbol' => 'en',
            'name' => 'English',
        ]);
        CategoryLanguageString::factory()->create([
            CategoryLanguageString::languageId => $language->id,
        ]);

        $endpoint = '/api/v1.0/category/search';
        $queryParams = [
            'login_user_id' => $this->user->{User::id},
            'limit' => 10,
            'offset' => 0,
            'searchterm' => '',
            'order_by' => '',
            'order_type' => '',
            'language_symbol' => 'en',
        ];
        $response = $this->postJson($endpoint.'?'.http_build_query($queryParams));

        $response->assertStatus(204);
    }

    public function test_category_search_api_returns_successful_response_and_structure_when_offest_is_zero_and_there_is_data(): void
    {
        $this->actingAs($this->user);

        $language = Language::factory()->create([
            'symbol' => 'en',
            'name' => 'English',
        ]);
        $category = Category::factory()->create([
            Category::status => 1,
        ]);
        CategoryLanguageString::factory()->create([
            CategoryLanguageString::languageId => $language->id,
            CategoryLanguageString::categoryId => $category->id,
        ]);

        $endpoint = '/api/v1.0/category/search';

        $queryParams = [
            'login_user_id' => $this->user->{User::id},
            'limit' => 10,
            'offset' => 0,
            'searchterm' => '',
            'order_by' => '',
            'order_type' => '',
            'language_symbol' => 'en',
        ];
        $response = $this->postJson($endpoint.'?'.http_build_query($queryParams));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'ordering',
                'status',
                'added_date',
                'default_photo',
                'default_icon',
                'added_date_str',
                'is_empty_object',
                'category_touch_count',
            ],
        ]);
    }

    public function test_category_search_api_returns_successful_response_when_offest_is_not_zero_and_there_is_no_data(): void
    {
        $this->actingAs($this->user);

        $language = Language::factory()->create([
            'symbol' => 'en',
            'name' => 'English',
        ]);
        CategoryLanguageString::factory()->create([
            CategoryLanguageString::languageId => $language->id,
        ]);

        $endpoint = '/api/v1.0/category/search';
        $queryParams = [
            'login_user_id' => $this->user->{User::id},
            'limit' => 10,
            'offset' => 10,
            'searchterm' => '',
            'order_by' => '',
            'order_type' => '',
            'language_symbol' => 'en',
        ];
        $response = $this->postJson($endpoint.'?'.http_build_query($queryParams));

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
    }

    public function test_category_search_api_returns_successful_response_and_structure_when_offest_is_not_zero_and_there_is_data(): void
    {
        $this->actingAs($this->user);

        $language = Language::factory()->create([
            'symbol' => 'en',
            'name' => 'English',
        ]);
        $categories = Category::factory()->count(20)->create([
            Category::status => 1,
        ]);
        foreach ($categories as $category) {
            CategoryLanguageString::factory()->create([
                CategoryLanguageString::languageId => $language->id,
                CategoryLanguageString::categoryId => $category->id,
            ]);
        }

        $endpoint = '/api/v1.0/category/search';

        $queryParams = [
            'login_user_id' => $this->user->{User::id},
            'limit' => 10,
            'offset' => 10,
            'searchterm' => '',
            'order_by' => '',
            'order_type' => '',
            'language_symbol' => 'en',
        ];
        $response = $this->postJson($endpoint.'?'.http_build_query($queryParams));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'ordering',
                'status',
                'added_date',
                'default_photo',
                'default_icon',
                'added_date_str',
                'is_empty_object',
                'category_touch_count',
            ],
        ]);
    }
}
