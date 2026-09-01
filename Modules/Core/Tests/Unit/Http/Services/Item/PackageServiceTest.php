<?php

namespace Modules\Core\Http\Tests\Unit\Http\Services\Item;

use Mockery;
use Modules\Core\Constants\Constants;
use Modules\Core\Http\Services\Financial\ItemCurrencyService;
use Modules\Core\Http\Services\Item\PackageService;
use Modules\Core\Http\Services\User\UserInfoService;
use RuntimeException;
use Tests\TestCase;

class PackageServiceTest extends TestCase
{
    protected $itemCurrencyService;

    protected $userInfoService;

    protected $packageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->itemCurrencyService = Mockery::mock(ItemCurrencyService::class);
        $this->userInfoService = Mockery::mock(UserInfoService::class);

        $this->packageService = Mockery::mock(PackageService::class, [
            $this->itemCurrencyService,
            $this->userInfoService,
        ])->makePartial();

    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // region isPaidItemUploadSettingEnabled

    public function test_is_paid_item_upload_setting_enabled_with_disable()
    {
        $result = $this->packageService->isPaidItemUploadSettingEnabled(null);
        $this->assertFalse($result);

        $result = $this->packageService->isPaidItemUploadSettingEnabled((object) ['is_paid_app' => 0]);
        $this->assertFalse($result);

        $result = $this->packageService->isPaidItemUploadSettingEnabled((object) ['wrong_key' => 0]);
        $this->assertFalse($result);

        $result = $this->packageService->isPaidItemUploadSettingEnabled((object) ['is_paid_app' => 'ABC']);
        $this->assertFalse($result);
    }

    public function test_is_paid_item_upload_setting_enabled_with_enabled()
    {

        $result = $this->packageService->isPaidItemUploadSettingEnabled((object) ['is_paid_app' => 1]);
        $this->assertTrue($result);

        $result = $this->packageService->isPaidItemUploadSettingEnabled((object) ['wrong_key' => 1]);
        $this->assertFalse($result);

    }

    // endregion

    // region hasSufficientBalance
    public function test_has_sufficient_balance_with_null()
    {
        $result = $this->packageService->hasSufficientBalance(null, null);
        $this->assertFalse($result);
    }

    public function test_has_sufficient_balance_with_valid_values()
    {
        $result = $this->packageService->hasSufficientBalance((object) ['value' => 1], null);
        $this->assertTrue($result);

        $result = $this->packageService->hasSufficientBalance((object) ['value' => 999.999], null);
        $this->assertTrue($result);
    }

    public function test_has_sufficient_balance_with_invalid_values()
    {
        $result = $this->packageService->hasSufficientBalance((object) ['value' => 'ABC'], null);
        $this->assertFalse($result);

        $result = $this->packageService->hasSufficientBalance((object) ['value' => -1], null);
        $this->assertFalse($result);

        $result = $this->packageService->hasSufficientBalance((object) ['value' => 0], null);
        $this->assertFalse($result);

        $result = $this->packageService->hasSufficientBalance((object) ['wrong_data' => 1], null);
        $this->assertFalse($result);
    }

    public function test_has_sufficient_balance_with_user()
    {
        $result = $this->packageService->hasSufficientBalance((object) ['value' => 0], (object) ['role_id' => Constants::superAdminRoleId]);
        $this->assertTrue($result);

        $result = $this->packageService->hasSufficientBalance((object) ['value' => 0], (object) ['role_id' => Constants::normalUserRoleId]);
        $this->assertFalse($result);

        $result = $this->packageService->hasSufficientBalance((object) ['value' => 1], (object) ['role_id' => Constants::normalUserRoleId]);
        $this->assertTrue($result);
    }
    // endregion

    // region consumeBalance

    public function test_consume_balance_with_disabled_system_config()
    {
        $result = $this->packageService->consumeBalance(null, null, null);
        $this->assertEquals(null, $result);
    }

    public function test_consume_balance_with_super_user()
    {
        $result = $this->packageService->consumeBalance(null, (object) ['role_id' => Constants::superAdminRoleId], null);
        $this->assertEquals(null, $result);

        $result = $this->packageService->consumeBalance((object) ['is_paid_app' => 1], (object) ['role_id' => Constants::superAdminRoleId], null);
        $this->assertEquals(null, $result);

        $result = $this->packageService->consumeBalance((object) ['is_paid_app' => 0], (object) ['role_id' => Constants::superAdminRoleId], null);
        $this->assertEquals(null, $result);
    }

    public function test_consume_balance_with_normal_user_and_null_remainig_post()
    {
        $result = $this->packageService->consumeBalance(null, (object) ['role_id' => Constants::normalUserRoleId], null);
        $this->assertEquals(null, $result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid user remaining post data.');
        $this->packageService->consumeBalance((object) ['is_paid_app' => 1], (object) ['role_id' => Constants::normalUserRoleId], null);

    }

    public function test_consume_balance_with_normal_user_and_invalid_remainig_post()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid user remaining post data.');
        $this->packageService->consumeBalance((object) ['is_paid_app' => 1], (object) ['role_id' => Constants::normalUserRoleId], (object) ['value' => 'ABC']);

    }

    public function test_consume_balance_with_normal_user_and_insufficient_remainig_post()
    {
        $result = $this->packageService->consumeBalance(null, (object) ['role_id' => Constants::normalUserRoleId], (object) ['value' => 0]);
        $this->assertEquals(null, $result);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insufficient Balance.');
        $this->packageService->consumeBalance((object) ['is_paid_app' => 1], (object) ['role_id' => Constants::normalUserRoleId], (object) ['value' => 0]);

    }

    public function test_consume_balance_with_normal_user_and_no_user_id()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User Id is missing.');
        $this->packageService->consumeBalance((object) ['is_paid_app' => 1], (object) ['role_id' => Constants::normalUserRoleId], (object) ['value' => 1]);

    }

    public function test_consume_balance_with_normal_user_and_correct_remaining()
    {

        $this->userInfoService->shouldReceive('update')
            ->once()
            ->with(
                1,
                [Constants::usrRemainingPost => 9]
            );

        $result = $this->packageService->consumeBalance((object) ['is_paid_app' => 1], (object) ['role_id' => Constants::normalUserRoleId], (object) ['value' => 10, 'user_id' => 1]);
        $this->assertEquals(9, $result);
    }
    // endregion
}
