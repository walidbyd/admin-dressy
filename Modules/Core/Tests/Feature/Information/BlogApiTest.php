<?php

namespace Modules\Core\Tests\Feature\Information;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Entities\Information\Blog;
use Tests\TestCase;

class BlogApiTest extends TestCase
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

    // region search
    // -------------------------------------------------------------------
    // search
    // -------------------------------------------------------------------
    public function test_blog_search_api_returns_no_content_response_when_offet_is_zero_and_there_is_no_data(): void
    {
        $this->actingAs($this->user);

        $endpoint = '/api/v1.0/blog/search';
        $queryParams = [
            'offset' => 0,
            'limit' => 10,
            'login_user_id' => $this->user->{User::id},
        ];

        $response = $this->postJson($endpoint.'?'.http_build_query($queryParams));

        $response->assertStatus(204);
    }

    public function test_blog_search_api_returns_successful_response_and_structure_when_offset_is_zero_and_there_is_data(): void
    {
        $this->actingAs($this->user);

        Blog::factory()->count(5)->create([
            Blog::status => 1,
        ]);

        $endpoint = '/api/v1.0/blog/search';
        $queryParams = [
            'offset' => 0,
            'limit' => 10,
            'login_user_id' => $this->user->{User::id},
        ];

        $response = $this->postJson($endpoint.'?'.http_build_query($queryParams));

        $response->assertStatus(200);

        $response->assertJsonStructure([
            '*' => $this->getBlogApiStructure(),
        ]);
    }

    public function test_blog_search_api_returns_successful_response_when_offset_is_not_zero_and_there_is_no_data(): void
    {
        $this->actingAs($this->user);

        $endpoint = '/api/v1.0/blog/search';
        $queryParams = [
            'offset' => 10,
            'limit' => 10,
            'login_user_id' => $this->user->{User::id},
        ];

        $response = $this->postJson($endpoint.'?'.http_build_query($queryParams));

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
    }

    public function test_blog_search_api_returns_successful_response_and_structure_when_offset_is_not_zero_and_there_is_data(): void
    {
        $this->actingAs($this->user);

        Blog::factory()->count(20)->create([
            Blog::status => 1,
        ]);

        $endpoint = '/api/v1.0/blog/search';
        $queryParams = [
            'offset' => 10,
            'limit' => 10,
            'login_user_id' => $this->user->{User::id},
        ];

        $response = $this->postJson($endpoint.'?'.http_build_query($queryParams));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => $this->getBlogApiStructure(),
        ]);
    }
    // endregion

    // region detail
    // -------------------------------------------------------------------
    // detail
    // -------------------------------------------------------------------
    public function test_blog_detail_api_returns_not_found_when_there_is_no_id_or_blog_id(): void
    {
        $this->actingAs($this->user);

        $endpoint = '/api/v1.0/blog/detail';

        $queryParams = [
            'login_user_id' => $this->user->{User::id},
        ];

        $response = $this->getJson($endpoint.'?'.http_build_query($queryParams));

        $response->assertStatus(404);
    }

    public function test_blog_detail_api_returns_successful_response_and_structure_when_there_is_id_or_blog_id(): void
    {
        $this->actingAs($this->user);

        $blog = Blog::factory()->create();
        $blogId = $blog->{Blog::id};

        $endpoint = '/api/v1.0/blog/detail';

        // With Id
        $queryParams = [
            Blog::id => $blogId,
            'login_user_id' => $this->user->{User::id},
        ];
        $response = $this->getJson($endpoint.'?'.http_build_query($queryParams));

        $response->assertStatus(200);
        $response->assertJsonStructure($this->getBlogApiStructure());

        // With Blog Id
        $queryParams = [
            'blogId' => $blogId,
            'login_user_id' => $this->user->{User::id},
        ];
        $response = $this->getJson($endpoint.'?'.http_build_query($queryParams));

        $response->assertStatus(200);
        $response->assertJsonStructure($this->getBlogApiStructure());
    }
    // endregion

    private function getBlogApiStructure()
    {
        return [
            'id',
            'name',
            'description',
            'location_city_id',
            'shop_id',
            'status',
            'city' => [
                'id',
                'name',
                'lat',
                'lng',
                'ordering',
                'status',
                'description',
                'touch_count',
                'is_featured',
                'featured_date',
                'cityRelation',
                'added_date_str',
                'is_empty_object',
            ],
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
                'is_empty_object',
            ],
            'added_date_str',
            'added_user_name',
            'added_date',
            'is_empty_object',
        ];
    }
}
