<?php

namespace Modules\Core\Tests\Unit\Http\Services\User;

use App\Http\Contracts\Core\PsInfoServiceInterface;
use App\Http\Contracts\Utilities\CustomFieldServiceInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\UserInfo;
use Modules\Core\Http\Services\User\UserInfoService;
use Tests\TestCase;

class UserInfoServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $psInfoService;

    protected $customFieldService;

    protected $userInfoService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->psInfoService = Mockery::mock(PsInfoServiceInterface::class);
        $this->customFieldService = Mockery::mock(CustomFieldServiceInterface::class);

        $this->userInfoService = new UserInfoService(
            $this->psInfoService,
            $this->customFieldService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // region save
    // -------------------------------------------------------------------
    // save
    // -------------------------------------------------------------------

    public function test_save()
    {
        $parentId = 1;
        $customFieldValues = [
            'custom_field_1' => 'first value',
            'custom_field_2' => 'second value',
        ];

        $this->psInfoService
            ->shouldReceive('save')
            ->once()
            ->with(
                Constants::user,
                $customFieldValues,
                $parentId,
                UserInfo::class,
                'user_id'
            )
            ->andReturnNull();

        $this->userInfoService->save($parentId, $customFieldValues);

        $this->assertTrue(true);
    }
    // endregion

    // region get
    // -------------------------------------------------------------------
    // get
    // -------------------------------------------------------------------

    public function test_get()
    {
        UserInfo::truncate();
        UserInfo::factory()->count(10)->create();
        $user = User::factory()->create();
        $userInfo = UserInfo::factory()->create([
            'user_id' => $user->id,
            'core_keys_id' => 'usr00001',
            'value' => 'Test Value',
            'added_user_id' => $user->id,
        ]);

        // Get without any parameter (always return first one)
        $firstResult = $this->userInfoService->get();
        $this->assertEquals(1, $firstResult->id);

        // Get with ID
        $idResult = $this->userInfoService->get($userInfo->id);
        $this->assertEquals($userInfo->id, $idResult->id);

        // Get with Relation
        $relationResult = $this->userInfoService->get($userInfo->id, ['owner']);
        $this->assertTrue($relationResult->relationLoaded('owner'));

        // Get with Parent ID (condition doesn't work alone, always return first one)
        $parentIdResult = $this->userInfoService->get(parentId: $user->id);
        $this->assertEquals(1, $parentIdResult->id);

        // Get with Core Keys Id (condition doesn't work alone, always return first one)
        $coreKeysIdResult = $this->userInfoService->get(coreKeysId: $userInfo->core_keys_id);
        $this->assertEquals(1, $coreKeysIdResult->id);

        // Get with Both Parent Id and Core Keys Id
        $parentIdAndCoreKeysIdResult = $this->userInfoService->get(parentId: $user->id, coreKeysId: $userInfo->core_keys_id);
        $this->assertEquals($userInfo->id, $parentIdAndCoreKeysIdResult->id);

        // Get which meets all conditions
        $matchedResult = $this->userInfoService->get($userInfo->id, 'owner', $user->id, $userInfo->core_keys_id);
        $this->assertEquals($userInfo->id, $matchedResult->id);
        $this->assertTrue($matchedResult->relationLoaded('owner'));
        $this->assertEquals($user->id, $matchedResult->user_id);
        $this->assertEquals($userInfo->value, $matchedResult->value);

        // Get which doesn't meets conditions
        $nullResult = $this->userInfoService->get($userInfo->id, 'owner', 9999, $userInfo->core_keys_id);
        $this->assertNull($nullResult);
    }
    // endregion
}
