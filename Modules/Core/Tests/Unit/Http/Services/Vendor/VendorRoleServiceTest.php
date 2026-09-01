<?php

namespace Modules\Core\Tests\Unit\Http\Services\Vendor;

use App\Http\Contracts\Vendor\VendorRolePermissionServiceInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Mockery;
use Modules\Core\Entities\Vendor\VendorRole;
use Modules\Core\Http\Services\Vendor\VendorRoleService;
use Tests\TestCase;

class VendorRoleServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $vendorRolePermissionService;

    protected $vendorRoleService;

    protected function setup(): void
    {
        parent::setUp();

        VendorRole::truncate();

        $this->vendorRolePermissionService = Mockery::mock(VendorRolePermissionServiceInterface::class);

        $this->vendorRoleService = new VendorRoleService($this->vendorRolePermissionService);
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

    public function test_get_all_with_relation_loads_relation()
    {
        VendorRole::factory()->create();

        $result = $this->vendorRoleService->getAll(['owner']);

        $this->assertTrue($result->first()->relationLoaded('owner'));
    }

    public function test_get_all_with_condition_returns_vendor_role()
    {
        $activeOwner = VendorRole::factory()->create([
            VendorRole::name => 'Owner',
            VendorRole::description => 'Owner Description',
            VendorRole::status => 1,
        ]);
        $activeManager = VendorRole::factory()->create([
            VendorRole::name => 'Manager',
            VendorRole::description => 'Manager Description',
            VendorRole::status => 1,
        ]);
        $activeEmployer = VendorRole::factory()->create([
            VendorRole::name => 'Employer',
            VendorRole::description => 'Employer Description',
            VendorRole::status => 1,
        ]);
        $inactiveManager = VendorRole::factory()->create([
            VendorRole::name => 'Manager',
            VendorRole::status => 0,
        ]);

        // Find with Name keyword
        $keywordNameResult = $this->vendorRoleService->getAll(conds: ['keyword' => 'Owner']);
        $this->assertEquals($activeOwner->id, $keywordNameResult->first()->id);

        // Find with Description keyword
        $keywordDescriptionResult = $this->vendorRoleService->getAll(conds: ['keyword' => 'Employer']);
        $this->assertEquals($activeEmployer->id, $keywordDescriptionResult->first()->id);

        // Find with Status
        $statusResult = $this->vendorRoleService->getAll(conds: [VendorRole::status => 0]);
        $this->assertCount(1, $statusResult);
        $this->assertEquals($inactiveManager->id, $statusResult->first()->id);

        // Order By with ID
        $orderByIdDescResult = $this->vendorRoleService->getAll(conds: ['order_by' => VendorRole::id, 'order_type' => 'desc']);
        $this->assertEquals($inactiveManager->id, $orderByIdDescResult[0]->id);

        $orderByIdAscResult = $this->vendorRoleService->getAll(conds: ['order_by' => VendorRole::id, 'order_type' => 'asc']);
        $this->assertEquals($activeOwner->id, $orderByIdAscResult[0]->id);

        // Order By with Column
        $orderByNameResult = $this->vendorRoleService->getAll(conds: ['order_by' => VendorRole::status, 'order_type' => 'asc']);
        $this->assertGreaterThan($orderByNameResult[0]->status, $orderByNameResult[1]->status);
        $this->assertEquals(0, $statusResult->first()->status);

        // Default Order (latest)
        $defaultOrderResult = $this->vendorRoleService->getAll();

        for ($i = 0; $i < $defaultOrderResult->count() - 1; $i++) {
            $this->assertLessThanOrEqual($defaultOrderResult[$i + 1]->added_date, $defaultOrderResult[$i]->added_date);
        }
    }

    public function test_get_all_with_role_ids_returns_vendor_role()
    {
        VendorRole::factory()->count(4)->create();
        $firstVendorRole = VendorRole::factory()->create();
        $secondVendorRole = VendorRole::factory()->create();

        $firstResult = $this->vendorRoleService->getAll(roleIds: [$firstVendorRole->id]);
        $this->assertCount(1, $firstResult);
        $this->assertEquals($firstVendorRole->id, $firstResult->first()->id);

        $secondResult = $this->vendorRoleService->getAll(roleIds: [$firstVendorRole->id, $secondVendorRole->id]);
        $this->assertCount(2, $secondResult);
    }

    public function test_get_all_filter_with_status()
    {
        VendorRole::factory()->count(10)->create();
        VendorRole::factory()->count(4)->create([VendorRole::status => 0]);

        $activeStatus = $this->vendorRoleService->getAll(status: 1);
        $this->assertCount(10, $activeStatus);

        $inactiveStatus = $this->vendorRoleService->getAll(status: 0);
        $this->assertCount(4, $inactiveStatus);
    }

    public function test_get_all_returns_paginator()
    {
        VendorRole::factory()->count(5)->create();

        $result = $this->vendorRoleService->getAll(pagPerPage: 2);
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        $result = $this->vendorRoleService->getAll(noPagination: true, pagPerPage: 2);
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);

        $result = $this->vendorRoleService->getAll(noPagination: true);
        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_get_all_with_all_params()
    {
        VendorRole::factory()->count(10)->create();
        $vendorRoleIds = VendorRole::factory()->count(5)->create([
            VendorRole::name => 'Test Role',
        ])->pluck(VendorRole::id);

        $result = $this->vendorRoleService->getAll('owner', [VendorRole::name => 'Test Role'], false, 10, null, $vendorRoleIds, 1);
        $this->assertTrue($result->first()->relationLoaded('owner'));
        $this->assertEquals('Test Role', $result->first()->{VendorRole::name});
        $this->assertCount(5, $result);
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }
    // endregion
}
