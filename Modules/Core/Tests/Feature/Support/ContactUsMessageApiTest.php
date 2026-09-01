<?php

namespace Modules\Core\Tests\Feature\Support;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Entities\Configuration\BackendSetting;
use Modules\Core\Entities\Information\CoreAbout;
use Modules\Core\Entities\Support\Contact;
use Tests\TestCase;

class ContactUsMessageApiTest extends TestCase
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

    // region contact
    // -------------------------------------------------------------------
    // contact
    // -------------------------------------------------------------------
    public function test_contact_us_api_without_any_data_returns_bad_request_response(): void
    {
        $this->actingAs($this->user);

        $endpoint = '/api/v1.0/contact';
        $queryParams = [
            'login_user_id' => $this->user->{User::id},
        ];

        $response = $this->postJson($endpoint.'?'.http_build_query($queryParams));

        $response->assertStatus(400);
    }

    public function test_contact_us_api_with_data_returns_failed_response_and_structure_when_email_failed_to_send(): void
    {
        $this->actingAs($this->user);

        $endpoint = '/api/v1.0/contact';
        $queryParams = [
            'login_user_id' => $this->user->{User::id},
        ];
        $contactData = [
            'contact_name' => 'Test User',
            'contact_email' => 'user@gmail.com',
            'contact_message' => 'Contact Message',
        ];

        $response = $this->postJson($endpoint.'?'.http_build_query($queryParams), $contactData);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'error',
            'status',
        ]);
    }

    public function test_contact_us_api_with_data_returns_successful_response_and_structure(): void
    {
        $this->actingAs($this->user);

        BackendSetting::factory()->create();

        $endpoint = '/api/v1.0/contact';
        $queryParams = [
            'login_user_id' => $this->user->{User::id},
        ];
        $contactData = [
            'contact_name' => 'Test User',
            'contact_email' => 'user@gmail.com',
            'contact_message' => 'Contact Message',
        ];

        $response = $this->postJson($endpoint.'?'.http_build_query($queryParams), $contactData);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'contact_name',
            'contact_email',
            'contact_phone',
            'contact_message',
            'added_date_str',
        ]);
    }

    public function test_contact_us_api_with_data_store_data_in_database(): void
    {
        $this->actingAs($this->user);

        BackendSetting::factory()->create();

        $endpoint = '/api/v1.0/contact';
        $queryParams = [
            'login_user_id' => $this->user->{User::id},
        ];
        $contactData = [
            'contact_name' => 'Test User',
            'contact_email' => 'user@gmail.com',
            'contact_message' => 'Contact Message',
        ];

        $response = $this->postJson($endpoint.'?'.http_build_query($queryParams), $contactData);

        $response->assertStatus(200);
        $this->assertDatabaseHas(Contact::tableName, $contactData);
    }
    // endregion

    // region getInTouchForContact
    // -------------------------------------------------------------------
    // getInTouchForContact
    // -------------------------------------------------------------------
    public function test_get_in_touch_api_returns_successful_response_and_structure(): void
    {
        $this->actingAs($this->user);

        CoreAbout::factory()->create();

        $endpoint = '/api/v1.0/contact/get_in_touch';
        $queryParams = [
            'login_user_id' => $this->user->{User::id},
        ];
        $response = $this->getJson($endpoint.'?'.http_build_query($queryParams));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'about_email',
            'about_phone',
            'about_address',
        ]);
    }
    // endregion
}
