<?php

namespace Tests\Unit\Location;

use App\Http\Contracts\Location\LocationCityServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Http\Controllers\Backend\Rests\App\V1_0\Location\LocationCityApiController;
use Tests\TestCase;

class LocationCityApiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected LocationCityApiController $locationCityApiController;

    protected function setUp(): void
    {
        // @todo Skip all tests in this class
        $this->markTestSkipped('This entire test class is currently disabled.');

        parent::setUp();

        $this->locationCityApiController = new LocationCityApiController(
            app(LocationCityServiceInterface::class),
        );

    }

    public function test_dummy()
    {
        $this->assertTrue(true);
    }
}
