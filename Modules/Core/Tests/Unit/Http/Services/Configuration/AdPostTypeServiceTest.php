<?php

namespace Modules\Core\Tests\Unit\Http\Services\Configuration;

use App\Http\Contracts\Configuration\SystemConfigServiceInterface;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\Configuration\AdPostType;
use Modules\Core\Entities\Configuration\SystemConfig;
use Modules\Core\Http\Services\Configuration\AdPostTypeService;
use Tests\TestCase;

class AdPostTypeServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $systemConfigService;

    protected $adPostTypeService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->systemConfigService = Mockery::mock(SystemConfigServiceInterface::class);

        $this->adPostTypeService = new AdPostTypeService(
            $this->systemConfigService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // region getAdPostType
    // -------------------------------------------------------------------
    // getAdPostType
    // -------------------------------------------------------------------

    public function test_get_ad_post_type_with_empty_or_null_ad_post_type_returns_default_ad_post_type()
    {
        AdPostType::truncate();
        $defaultAdPostType = AdPostType::factory()->create();
        $this->systemConfigService->shouldReceive('get')->andReturn((object) [
            SystemConfig::adType => $defaultAdPostType->{AdPostType::id},
        ]);

        // Null
        $nullResult = $this->adPostTypeService->getAdPostType();
        $this->assertEquals($defaultAdPostType->{AdPostType::key}, $nullResult);

        // Empty String
        $emptyAdPostType = $this->adPostTypeService->getAdPostType('');
        $this->assertEquals($defaultAdPostType->{AdPostType::key}, $emptyAdPostType);
    }

    public function test_get_ad_post_type_with_valid_ad_post_type_returns_valid_ad_post_type()
    {
        AdPostType::truncate();
        $defaultAdPostType = AdPostType::factory()->create([
            AdPostType::id => 1,
            AdPostType::key => 'test_ad_post_type_1',
        ]);
        $otherAdPostType = AdPostType::factory()->create([
            AdPostType::id => 2,
            AdPostType::key => 'test_ad_post_type_2',
        ]);

        $this->systemConfigService->shouldReceive('get')->andReturn((object) [
            SystemConfig::adType => $defaultAdPostType->{AdPostType::id},
        ]);

        // Did not select the default ad post from System Config
        $adPost1 = $this->adPostTypeService->getAdPostType($otherAdPostType->{AdPostType::key});
        $this->assertEquals($otherAdPostType->{AdPostType::key}, $adPost1);

        // Default ad post from System Config is selected
        $adPost2 = $this->adPostTypeService->getAdPostType($defaultAdPostType->{AdPostType::key});
        $this->assertEquals($defaultAdPostType->{AdPostType::key}, $adPost2);
    }

    public function test_get_ad_post_type_with_constant_ad_post_type_returns_constant_ad_post_type()
    {
        AdPostType::truncate();
        $defaultAdPostType = AdPostType::factory()->create([
            AdPostType::id => 1,
            AdPostType::key => 'test_ad_post_type_1',
        ]);
        AdPostType::factory()->count(5)->create();

        $this->systemConfigService->shouldReceive('get')->andReturn((object) [
            SystemConfig::adType => $defaultAdPostType->{AdPostType::id},
        ]);

        // Paid Item only constant
        $adPost1 = $this->adPostTypeService->getAdPostType(Constants::onlyPaidItemAdType);
        $this->assertEquals(Constants::onlyPaidItemAdType, $adPost1);

        // Paid Item first with Google constant
        $adPost2 = $this->adPostTypeService->getAdPostType(Constants::paidItemFirstWithGoogleAdType);
        $this->assertEquals(Constants::paidItemFirstWithGoogleAdType, $adPost2);
    }

    public function test_get_ad_post_type_with_invalid_ad_post_type_returns_default_ad_post_type()
    {
        $defaultAdPost = AdPostType::factory()->create([
            AdPostType::key => 'test_ad_post_type_1',
        ]);
        AdPostType::factory()->count(5)->create();

        $this->systemConfigService->shouldReceive('get')->andReturn((object) [
            SystemConfig::adType => $defaultAdPost->{AdPostType::id},
        ]);

        // Invalid Ad Post Type given
        $adPost = $this->adPostTypeService->getAdPostType('non_existent_ad_post_type');
        $this->assertEquals($defaultAdPost->{AdPostType::key}, $adPost);
    }

    public function test_get_ad_post_type_throws_exception_when_something_fails()
    {
        $this->systemConfigService->shouldReceive('get')->andThrow(Exception::class);

        $this->expectException(Exception::class);

        $result = $this->adPostTypeService->getAdPostType('invalid');
        $this->assertEquals('1', $result);
    }
    // endregion
}
