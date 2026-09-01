<?php

use App\Http\Contracts\Configuration\MobileSettingServiceInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Entities\Configuration\SystemConfig;
use Modules\Core\Http\Facades\PsCache;
use Modules\Core\Http\Services\Configuration\SettingService;
use Modules\Core\Http\Services\Configuration\SystemConfigService;
use Tests\TestCase;

class SystemConfigServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $systemConfigService;

    protected $mobileSettingService;

    protected $settingService;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mobileSettingService = Mockery::mock(MobileSettingServiceInterface::class);
        $this->settingService = Mockery::mock(SettingService::class);
        $this->systemConfigService = new SystemConfigService(
            Mockery::mock(MobileSettingServiceInterface::class),
            Mockery::mock(SettingService::class),
        );

        $this->user = User::factory()->create([User::roleId => '1']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // region get
    // -------------------------------------------------------------------
    // get
    // -------------------------------------------------------------------

    public function test_get_returns_first_config_without_parameters()
    {
        SystemConfig::truncate();
        $config = $this->createSystemConfig(1)->first();

        $result = $this->systemConfigService->get();

        $this->assertEquals($config->id, $result->id);
    }

    public function test_get_returns_config_by_id_only()
    {
        $this->createSystemConfig(3);
        $targetConfig = $this->createSystemConfig(1)->first();

        $result = $this->systemConfigService->get($targetConfig->id);

        $this->assertEquals($targetConfig->id, $result->id);
    }

    public function test_get_returns_config_with_relation_loaded()
    {
        $config = $this->createSystemConfig(1)->first();

        $result = $this->systemConfigService->get($config->id, ['owner']);

        $this->assertTrue($result->relationLoaded('owner'));
    }

    public function test_get_returns_null_when_id_does_not_exist()
    {
        $result = $this->systemConfigService->get(9999);
        $this->assertNull($result);
    }

    public function test_get_uses_cached_result_on_second_call()
    {
        $config = $this->createSystemConfig(1)->first();

        PsCache::shouldReceive('remember')
            ->once()
            ->andReturn($config);

        $result = $this->systemConfigService->get($config->id);

        $this->assertEquals($config->id, $result->id);
    }
    // endregion

    private function createSystemConfig(int $count = 1)
    {
        $configs = collect();

        for ($i = 0; $i < $count; $i++) {
            $configs->push(SystemConfig::create([
                SystemConfig::lat => 16.879910 + $i, // vary values if needed
                SystemConfig::lng => 96.173248 + $i,
                SystemConfig::isApprovedEnabled => 0,
                SystemConfig::isSubLocation => 0,
                SystemConfig::isThumb2x3xGenerate => 1,
                SystemConfig::isSubscription => 1,
                SystemConfig::isPaidApp => 0,
                SystemConfig::isPromoteEnable => 1,
                SystemConfig::freeAdPostCount => 1,
                SystemConfig::isBlockUser => 0,
                SystemConfig::maxImgUploadOfItem => 5,
                SystemConfig::adType => 5,
                SystemConfig::promoCellIntervalNo => 3,
                SystemConfig::oneDayPerPrice => 5,
                SystemConfig::addedUserId => $this->user->id,
            ]));
        }

        return $configs;
    }
}
