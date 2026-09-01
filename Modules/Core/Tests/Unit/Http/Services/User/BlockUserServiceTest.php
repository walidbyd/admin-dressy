<?php

namespace Modules\Core\Tests\Unit\Http\Services\User;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Modules\Core\Entities\User\BlockUser;
use Modules\Core\Http\Services\User\BlockUserService;
use Tests\TestCase;

class BlockUserServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $blockUserService;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([User::roleId => '1']);

        $this->blockUserService = new BlockUserService;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // region getAll
    // -------------------------------------------------------------------
    // getAll
    // -------------------------------------------------------------------

    public function test_get_all_with_relation_eager_loads_relationships()
    {
        BlockUser::factory()->create();

        $result = $this->blockUserService->getAll(['blockedUser']);

        $this->assertTrue($result->first()->relationLoaded('blockedUser'));
    }

    public function test_get_all_with_conditions_applies_basic_where_clauses()
    {
        $user = User::factory()->create();
        $matchingBlock = BlockUser::factory()->create([BlockUser::addedUserId => $user->id]);
        BlockUser::factory()->create();

        $result = $this->blockUserService->getAll(null, [BlockUser::addedUserId => $user->id]);

        $this->assertCount(1, $result);
        $this->assertEquals($matchingBlock->id, $result->first()->id);
    }

    public function test_get_all_with_from_block_user_id_condition_applies_special_filter()
    {
        BlockUser::factory()->create();
        $user = User::factory()->create();
        $matchingBlock = BlockUser::factory()->create([BlockUser::fromBlockUserId => $user->id]);

        $result = $this->blockUserService->getAll(null, ['from_block_user_id' => $user->id]);

        $this->assertCount(1, $result);
        $this->assertEquals($user->id, $result->first()->{BlockUser::fromBlockUserId});
    }

    public function test_get_all_with_limit_returns_limited_records()
    {
        BlockUser::factory()->count(3)->create();

        $result = $this->blockUserService->getAll(null, null, 2);

        $this->assertCount(2, $result);
    }

    public function test_get_all_with_limit_and_offset_skips_records()
    {
        BlockUser::truncate();
        $blocks = BlockUser::factory()->count(3)->create()->sortByDesc(BlockUser::addedDate);

        $result = $this->blockUserService->getAll(null, null, 5, 1);

        $this->assertCount(2, $result);
        $this->assertEquals($blocks[1]->id, $result->first()->id);
    }

    public function test_get_all_returns_all_records_in_latest_order()
    {
        BlockUser::truncate();
        $olderBlock = BlockUser::factory()->create([BlockUser::addedDate => now()->subDay()]);
        $newerBlock = BlockUser::factory()->create([BlockUser::addedDate => now()]);

        $result = $this->blockUserService->getAll();

        $this->assertCount(2, $result);
        $this->assertEquals($newerBlock->id, $result->first()->id);
        $this->assertEquals($olderBlock->id, $result->last()->id);
    }

    public function test_get_all_combines_all_parameters_correctly()
    {
        $user = User::factory()->create();
        $matchingBlock = BlockUser::factory()->create([
            BlockUser::fromBlockUserId => $user->id,
            BlockUser::addedUserId => $user->id,
            BlockUser::addedDate => now()->subHour(),
        ]);
        BlockUser::factory()->create([BlockUser::addedUserId => $user->id]);
        BlockUser::factory()->create();

        $result = $this->blockUserService->getAll(
            ['blockedUser'],
            ['from_block_user_id' => $user->id],
            1,
            0
        );

        $this->assertCount(1, $result);
        $this->assertEquals($user->id, $result->first()->{BlockUser::addedUserId});
        $this->assertTrue($result->first()->relationLoaded('blockedUser'));
    }
    // endregion
}
