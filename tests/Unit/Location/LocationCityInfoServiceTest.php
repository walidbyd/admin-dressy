<?php

namespace Tests\Unit\Location;

use App\Http\Contracts\Core\PsInfoServiceInterface;
use App\Http\Contracts\Utilities\CustomFieldServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\Location\LocationCityInfo;
use Modules\Core\Http\Services\Location\LocationCityInfoService;
use Tests\TestCase;

class LocationCityInfoServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $locationCityInfoServiceOriginal;

    protected $customFieldService;

    protected $psInfoService;

    protected function setUp(): void
    {
        // @todo Skip all tests in this class
        $this->markTestSkipped('This entire test class is currently disabled.');

        parent::setUp();

        $this->customFieldService = Mockery::mock(CustomFieldServiceInterface::class);
        $this->psInfoService = Mockery::mock(PsInfoServiceInterface::class);

        $this->locationCityInfoServiceOriginal = new LocationCityInfoService(
            $this->psInfoService,
            $this->customFieldService
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up Mockery
        Mockery::close();
    }

    // //////////////////////////////////////////////////////////////////
    // / Public Function Test Cases
    // //////////////////////////////////////////////////////////////////

    public function test_update()
    {
        // prepare Data
        $parentId = 1;
        $value = 'Testing';
        $loc00001 = 'loc00001';
        $customFieldValues = [
            $loc00001 => $value,
        ];

        LocationCityInfo::factory()->create([
            'location_city_id' => $parentId,
            'core_keys_id' => $loc00001,
        ]);

        // Mock
        $this->psInfoService->shouldReceive('update')
            ->once()
            ->with(
                Constants::locationCity,
                $customFieldValues,
                $parentId,
                LocationCityInfo::class,
                'location_city_id'
            );

        $this->psInfoService->shouldReceive('deleteAll')
            ->with(Mockery::on(function ($arg) {
                return $arg->count() === 1 &&
                    $arg->contains('core_keys_id', 'loc00001');
            }))
            ->once();

        // Call the method
        $this->locationCityInfoServiceOriginal->update($parentId, $customFieldValues);
        $successResult = $this->locationCityInfoServiceOriginal->get(1);

        $this->assertEquals($value, $successResult->value);
    }
}
