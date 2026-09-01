<?php

namespace Modules\Core\Tests\Unit\Http\Services\Utilities;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\Category\Category;
use Modules\Core\Entities\Utilities\CustomField;
use Modules\Core\Http\Services\Utilities\CustomFieldService;
use Tests\TestCase;

class CustomFieldServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $customFieldService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customFieldService = new CustomFieldService;

    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
    // region get
    // -------------------------------------------------------------------
    // get
    // -------------------------------------------------------------------

    public function test_get_with_id_returns_custom_field()
    {
        CustomField::factory()->count(1)->create();
        $customField = CustomField::factory()->create();

        $result = $this->customFieldService->get($customField->{CustomField::id});

        $this->assertEquals($customField->{CustomField::id}, $result->{CustomField::id});
    }

    public function test_get_with_table_id_returns_custom_field()
    {
        CustomField::factory()->count(1)->create();
        $customField = CustomField::factory()->create([
            CustomField::tableId => 2,
        ]);

        $result = $this->customFieldService->get(tableId: $customField->{CustomField::tableId});

        $this->assertEquals($customField->{CustomField::tableId}, $result->{CustomField::tableId});
    }

    public function test_get_with_relation_loads_relation()
    {
        CustomField::factory()->count(1)->create();

        $result = $this->customFieldService->get(relation: ['owner']);

        $this->assertTrue($result->relationLoaded('owner'));
        $this->assertNotNull($result->owner);
    }

    public function test_get_with_core_keys_id_returns_custom_field()
    {
        CustomField::factory()->count(1)->create();
        $customField = CustomField::factory()->create([
            CustomField::coreKeysId => 'unique-key',
        ]);

        $result = $this->customFieldService->get(coreKeysId: $customField->{CustomField::coreKeysId});

        $this->assertEquals($customField->{CustomField::id}, $result->{CustomField::id});
        $this->assertEquals($customField->{CustomField::coreKeysId}, $result->{CustomField::coreKeysId});
    }

    public function test_get_with_code_returns_custom_field()
    {
        CustomField::factory()->count(1)->create();
        $customField = CustomField::factory()->create([
            CustomField::moduleName => 'unique-module',
        ]);

        $result = $this->customFieldService->get(code: $customField->{CustomField::moduleName});

        $this->assertEquals($customField->{CustomField::id}, $result->{CustomField::id});
        $this->assertEquals($customField->{CustomField::moduleName}, $result->{CustomField::moduleName});
    }

    public function test_get_with_all_matching_param_returns_custom_field()
    {
        CustomField::factory()->count(1)->create();
        $customField = CustomField::factory()->create([
            CustomField::moduleName => 'unique-module',
        ]);

        $result = $this->customFieldService->get($customField->{CustomField::id}, $customField->{CustomField::tableId}, ['owner'], $customField->{CustomField::coreKeysId}, $customField->{CustomField::moduleName});

        $this->assertEquals($customField->{CustomField::id}, $result->{CustomField::id});
        $this->assertEquals($customField->{CustomField::tableId}, $result->{CustomField::tableId});
        $this->assertTrue($result->relationLoaded('owner'));
        $this->assertNotNull($result->owner);
        $this->assertEquals($customField->{CustomField::coreKeysId}, $result->{CustomField::coreKeysId});
        $this->assertEquals($customField->{CustomField::moduleName}, $result->{CustomField::moduleName});
    }

    public function test_get_with_non_matching_param_returns_null()
    {
        CustomField::factory()->count(1)->create();
        $customField = CustomField::factory()->create([
            CustomField::moduleName => 'unique-module',
        ]);

        $result = $this->customFieldService->get($customField->{CustomField::id}, 3023, ['owner'], $customField->{CustomField::coreKeysId}, $customField->{CustomField::moduleName});

        $this->assertNull($result);
    }
    // endregion

    // region getAll
    // -------------------------------------------------------------------
    // getAll
    // -------------------------------------------------------------------

    public function test_get_all_with_relation_loads_relation()
    {
        CustomField::all()->each->delete();
        CustomField::factory()->count(5)->create();

        $result = $this->customFieldService->getAll(['owner']);

        $this->assertCount(5, $result);
        foreach ($result as $customField) {
            $this->assertTrue($customField->relationLoaded('owner'));
            $this->assertNotNull($customField->owner);
        }
    }

    public function test_get_all_with_table_id_returns_custom_field()
    {
        CustomField::factory()->count(1)->create();
        CustomField::factory()->count(4)->create([
            CustomField::tableId => 2,
        ]);

        $result = $this->customFieldService->getAll(tableId: 2);

        $this->assertCount(4, $result);
    }

    public function test_get_all_with_no_pag_returns_paginator()
    {
        CustomField::all()->each->delete();
        CustomField::factory()->count(2)->create();

        $result = $this->customFieldService->getAll(withNoPag: false);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(2, $result);
    }

    public function test_get_all_with_pag_returns_collection()
    {
        CustomField::all()->each->delete();
        CustomField::factory()->count(2)->create();

        $result = $this->customFieldService->getAll(withNoPag: true);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    public function test_get_all_with_table_ids_returns_custom_field()
    {
        CustomField::all()->each->delete();
        CustomField::factory()->count(2)->create([
            CustomField::tableId => 1,
        ]);
        CustomField::factory()->count(2)->create([
            CustomField::tableId => 2,
        ]);

        $result = $this->customFieldService->getAll(tableIds: [1, 2]);

        $this->assertCount(4, $result);
    }

    public function test_get_all_with_core_keys_id_returns_custom_field()
    {
        // CustomField::truncate();
        CustomField::factory()->count(1)->create();
        CustomField::factory()->create([
            CustomField::coreKeysId => 'key-1',
        ]);
        CustomField::factory()->create([
            CustomField::coreKeysId => 'key-2',
        ]);

        $result = $this->customFieldService->getAll(coreKeysIds: ['key-1', 'key-2']);

        $this->assertCount(2, $result);
    }

    public function test_get_all_with_sort_and_order_returns_sorted_custom_field()
    {
        CustomField::factory()->count(3)->create();

        // Ascending
        $result = $this->customFieldService->getAll(sort: CustomField::id, order: Constants::ascending);

        $ids = $result->pluck(CustomField::id)->toArray();
        $sortedIds = $ids;
        sort($sortedIds);

        $this->assertEquals($sortedIds, $ids);

        // Descending
        $result = $this->customFieldService->getAll(sort: CustomField::id, order: Constants::descending);

        $ids = $result->pluck(CustomField::id)->toArray();
        $sortedIds = $ids;
        rsort($sortedIds);

        $this->assertEquals($sortedIds, $ids);

        // Ui Type Id (gets ignored)
        $result = $this->customFieldService->getAll(sort: CustomField::uiTypeId, order: Constants::descending);

        $coreKeysIds = $result->pluck(CustomField::uiTypeId)->toArray();
        $sortedCoreKeysIds = $coreKeysIds;
        rsort($sortedCoreKeysIds);

        $this->assertNotEquals($sortedCoreKeysIds, $coreKeysIds);
    }

    public function test_get_all_with_search_returns_custom_field()
    {
        // CustomField::truncate();
        CustomField::factory()->count(1)->create();
        CustomField::factory()->create([
            CustomField::name => 'SearchResult1',
        ]);
        CustomField::factory()->create([
            CustomField::name => 'SearchResult2',
        ]);

        $result = $this->customFieldService->getAll(search: 'Search');

        $this->assertCount(2, $result);
    }

    public function test_get_all_with_row_returns_paginator_with_row_count()
    {
        // CustomField::truncate();
        CustomField::factory()->count(7)->create();

        $result = $this->customFieldService->getAll(row: 5);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(5, $result);
    }

    public function test_get_all_with_is_delete_returns_custom_field()
    {
        CustomField::all()->each->delete();
        CustomField::factory()->count(2)->create([
            CustomField::isDelete => 0,
        ]);
        CustomField::factory()->count(1)->create([
            CustomField::isDelete => 1,
        ]);

        $result = $this->customFieldService->getAll(isDelete: 0);
        $this->assertCount(2, $result);

        $result = $this->customFieldService->getAll(isDelete: 1);
        $this->assertCount(1, $result);
    }

    public function test_get_all_with_ids_returns_custom_field()
    {
        // CustomField::truncate();
        CustomField::factory()->count(1)->create();
        $customField1 = CustomField::factory()->create();
        $customField2 = CustomField::factory()->create();

        $result = $this->customFieldService->getAll(ids: [$customField1->id, $customField2->id]);
        $this->assertCount(2, $result);
    }

    public function test_get_all_with_code_returns_custom_field()
    {
        // CustomField::truncate();
        CustomField::factory()->count(1)->create([
            CustomField::moduleName => 'module-1',
        ]);
        CustomField::factory()->count(2)->create([
            CustomField::moduleName => 'module-2',
        ]);

        $result = $this->customFieldService->getAll(code: 'module-1');
        $this->assertCount(1, $result);

        $result = $this->customFieldService->getAll(code: 'module-2');
        $this->assertCount(2, $result);
    }

    public function test_get_all_with_not_start_with_at_core_keys_id_col_returns_custom_field()
    {
        CustomField::all()->each->delete();
        CustomField::factory()->count(2)->create();
        CustomField::factory()->create([
            CustomField::coreKeysId => 'notLikeCoreKey1',
        ]);
        CustomField::factory()->create([
            CustomField::coreKeysId => 'notLikeCoreKey2',
        ]);

        $result = $this->customFieldService->getAll(notStartWithAtCoreKeysIdCol: 'notLikeCore');
        $this->assertCount(2, $result);
    }

    public function test_get_all_with_is_core_field_returns_custom_field()
    {
        CustomField::all()->each->delete();
        CustomField::factory()->count(1)->create([
            CustomField::isCoreField => 0,
        ]);
        CustomField::factory()->count(2)->create([
            CustomField::isCoreField => 1,
        ]);

        $result = $this->customFieldService->getAll(isCoreField: 0);
        $this->assertCount(1, $result);

        $result = $this->customFieldService->getAll(isCoreField: 1);
        $this->assertCount(2, $result);
    }

    public function test_get_all_with_module_name_returns_custom_field()
    {
        // CustomField::truncate();
        CustomField::factory()->count(1)->create([
            CustomField::moduleName => 'module-1',
        ]);
        CustomField::factory()->count(2)->create([
            CustomField::moduleName => 'module-2',
        ]);

        $result = $this->customFieldService->getAll(moduleName: 'module-1');
        $this->assertCount(1, $result);

        $result = $this->customFieldService->getAll(moduleName: 'module-2');
        $this->assertCount(2, $result);
    }

    public function test_get_all_with_is_latest_returns_latest_custom_field()
    {
        CustomField::all()->each->delete();
        CustomField::factory()->create([CustomField::addedDate => now()->subMinutes(5)]);
        CustomField::factory()->create([CustomField::addedDate => now()->subMinutes(4)]);
        CustomField::factory()->create([CustomField::addedDate => now()->subMinutes(3)]);
        CustomField::factory()->create([CustomField::addedDate => now()->subMinutes(2)]);
        CustomField::factory()->create([CustomField::addedDate => now()->subMinutes(1)]);

        $result = $this->customFieldService->getAll(isLatest: true);
        $customFieldIds = $result->pluck(CustomField::id)->toArray();
        rsort($customFieldIds);
        $this->assertEquals($customFieldIds, $result->pluck(CustomField::id)->toArray());
    }

    public function test_get_all_with_ui_type_id_returns_custom_field()
    {
        // CustomField::truncate();
        CustomField::factory()->count(1)->create();
        CustomField::factory()->count(2)->create([
            CustomField::uiTypeId => 'custom-123',
        ]);

        $result = $this->customFieldService->getAll(uiTypeId: 'custom-123');
        $this->assertCount(2, $result);
    }

    public function test_get_all_without_category_id_returns_custom_field_without_category_id()
    {
        CustomField::all()->each->delete();
        $categoryId = Category::factory()->create();
        CustomField::factory()->count(2)->create();
        CustomField::factory()->count(1)->create([
            CustomField::categoryId => $categoryId,
        ]);

        $result = $this->customFieldService->getAll();
        $this->assertCount(2, $result);
    }

    public function test_get_all_with_category_id_zero_returns_custom_field_without_category_id()
    {
        CustomField::all()->each->delete();
        $categoryId = Category::factory()->create();
        CustomField::factory()->count(1)->create();
        CustomField::factory()->count(2)->create([
            CustomField::categoryId => $categoryId,
        ]);

        $result = $this->customFieldService->getAll(categoryId: '0');
        $this->assertCount(1, $result);
    }

    public function test_get_all_with_category_id_and_without_category_id_only_returns_custom_field()
    {
        // CustomField::truncate();
        CustomField::all()->each->delete();
        $categoryId = Category::factory()->create()->{Category::id};
        CustomField::factory()->count(1)->create();
        CustomField::factory()->count(2)->create([
            CustomField::categoryId => $categoryId,
        ]);

        $result = $this->customFieldService->getAll(categoryId: $categoryId);
        $this->assertCount(3, $result);
    }

    public function test_get_all_with_category_id_and_with_category_id_returns_custom_field_with_category_id()
    {
        // CustomField::truncate();
        $categoryId = Category::factory()->create()->{Category::id};
        CustomField::factory()->count(1)->create();
        CustomField::factory()->count(3)->create([
            CustomField::categoryId => $categoryId,
        ]);

        $result = $this->customFieldService->getAll(categoryId: $categoryId, categoryIdOnly: true);
        $this->assertCount(3, $result);
    }

    public function test_get_all_with_no_pag_and_limit_returns_custom_field()
    {
        CustomField::factory()->count(6)->create();

        $result = $this->customFieldService->getAll(withNoPag: true, limit: 5);
        $this->assertCount(5, $result);
    }

    public function test_get_all_with_no_pag_limit_and_offset_returns_custom_field()
    {
        CustomField::factory()->count(10)->create();

        $result = $this->customFieldService->getAll(withNoPag: true, limit: 3, offset: 5);
        $this->assertCount(3, $result);
    }
    // endregion
}
