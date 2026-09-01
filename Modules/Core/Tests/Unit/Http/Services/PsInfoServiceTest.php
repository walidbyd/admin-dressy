<?php

namespace Modules\Core\Tests\Unit\Http\Services;

use App\Http\Contracts\Utilities\CustomFieldServiceInterface;
use App\Http\Services\PsInfoService;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Mockery;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\Item\Item;
use Modules\Core\Entities\ItemInfo;
use Modules\Core\Entities\Utilities\CustomField;
use Tests\TestCase;

class PsInfoServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $customFieldService;

    protected $psInfoService;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customFieldService = Mockery::mock(CustomFieldServiceInterface::class);

        $this->psInfoService = new PsInfoService(
            $this->customFieldService
        );

        $this->user = User::factory()->create(['role_id' => '1']);
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
        $this->actingAs($this->user);

        $item = Item::factory()->create();
        $moduleName = 'itm';
        $customField1 = CustomField::factory()->create([
            CustomField::moduleName => $moduleName,
        ]);
        $customField2 = CustomField::factory()->create([
            CustomField::moduleName => $moduleName,
        ]);

        $customFieldValues = [
            $customField1->{CustomField::coreKeysId} => 'Test Value 1',
            $customField2->{CustomField::coreKeysId} => 'Test Value 2',
        ];

        $this->customFieldService
            ->shouldReceive('get')
            ->with(null, null, null, $customField1->{CustomField::coreKeysId}, $moduleName)
            ->andReturn($customField1);

        $this->customFieldService
            ->shouldReceive('get')
            ->with(null, null, null, $customField2->{CustomField::coreKeysId}, $moduleName)
            ->andReturn($customField2);

        $this->customFieldService
            ->shouldReceive('getAll')
            ->with(null, null, Constants::yes, null, null, null, null, null, null, Constants::unDelete, null, $moduleName)
            ->andReturn(collect([$customField1, $customField2]));

        $this->psInfoService->save(
            $moduleName,
            $customFieldValues,
            $item->{Item::id},
            ItemInfo::class,
            ItemInfo::itemId
        );

        $this->assertDatabaseHas(ItemInfo::tableName, [
            ItemInfo::coreKeysId => $customField1->{CustomField::coreKeysId},
            ItemInfo::value => 'Test Value 1',
        ]);
        $this->assertDatabaseHas(ItemInfo::tableName, [
            ItemInfo::coreKeysId => $customField2->{CustomField::coreKeysId},
            ItemInfo::value => 'Test Value 2',
        ]);
    }
    // endregion

    // region update
    // -------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------

    public function test_update_when_custom_field_values_returns_null()
    {
        $result = $this->psInfoService->update('itm', [], 1, ItemInfo::class, ItemInfo::itemId);

        $this->assertNull($result);
    }

    public function test_update()
    {
        $this->actingAs($this->user);

        $item = Item::factory()->create();
        $moduleName = 'itm';
        $customField1 = CustomField::factory()->create([
            CustomField::moduleName => $moduleName,
        ]);
        $customField2 = CustomField::factory()->create([
            CustomField::moduleName => $moduleName,
        ]);

        $customFieldValues = [
            $customField1->{CustomField::coreKeysId} => 'Updated Value',
            $customField2->{CustomField::coreKeysId} => 'Inserted Value',
        ];

        ItemInfo::factory()->create([
            ItemInfo::coreKeysId => $customField1->{CustomField::coreKeysId},
            ItemInfo::uiTypeId => $customField1->{CustomField::uiTypeId},
            ItemInfo::itemId => $item->{Item::id},
            ItemInfo::value => 'Test Value',
        ]);

        $this->customFieldService
            ->shouldReceive('get')
            ->with(null, null, null, $customField1->{CustomField::coreKeysId}, $moduleName)
            ->andReturn($customField1);

        $this->customFieldService
            ->shouldReceive('get')
            ->with(null, null, null, $customField2->{CustomField::coreKeysId}, $moduleName)
            ->andReturn($customField2);

        $this->psInfoService->update(
            $moduleName,
            $customFieldValues,
            $item->{Item::id},
            ItemInfo::class,
            ItemInfo::itemId
        );

        $this->assertDatabaseHas(ItemInfo::tableName, [
            ItemInfo::coreKeysId => $customField1->{CustomField::coreKeysId},
            ItemInfo::value => 'Updated Value',
        ]);
        $this->assertDatabaseHas(ItemInfo::tableName, [
            ItemInfo::coreKeysId => $customField2->{CustomField::coreKeysId},
            ItemInfo::value => 'Inserted Value',
        ]);
    }
    // endregion

    // region get
    // -------------------------------------------------------------------
    // get
    // -------------------------------------------------------------------

    // No need
    // public function test_get_returns_first_item_info()
    // {
    //     $itemInfo = ItemInfo::factory()->create();

    //     $result = $this->psInfoService->get(ItemInfo::class);

    //     $this->assertEquals($itemInfo->id, $result->item_id);
    // }

    public function test_get_with_core_key_id_return_first_item_info()
    {
        ItemInfo::factory()->count(5)->create();
        $itemInfo = ItemInfo::factory()->create();

        $result = $this->psInfoService->get(ItemInfo::class, null, $itemInfo->{ItemInfo::coreKeysId});

        $this->assertEquals($itemInfo->{ItemInfo::id}, $result->{ItemInfo::id});
        $this->assertEquals($itemInfo->{ItemInfo::coreKeysId}, $result->{ItemInfo::coreKeysId});
    }

    public function test_get_with_parent_id_and_parent_id_field_name_returns_first_item_info()
    {
        ItemInfo::factory()->count(5)->create();
        $item = Item::factory()->create();
        $itemInfo = ItemInfo::factory()->create([
            ItemInfo::itemId => $item->{Item::id},
        ]);

        $result = $this->psInfoService->get(ItemInfo::class, $item->{Item::id}, null, ItemInfo::itemId);

        $this->assertEquals($itemInfo->{ItemInfo::id}, $result->{ItemInfo::id});
        $this->assertEquals($itemInfo->{ItemInfo::coreKeysId}, $result->{ItemInfo::coreKeysId});
    }

    public function test_get_returns_null_when_no_matching_record_found()
    {
        ItemInfo::factory()->count(5)->create();
        $item = Item::factory()->create();

        $result = $this->psInfoService->get(ItemInfo::class, $item->{Item::id}, null, ItemInfo::itemId);

        $this->assertNull($result);
    }
    // endregion

    // region validatedData
    // -------------------------------------------------------------------
    // validatedData
    // -------------------------------------------------------------------

    public function test_validated_data_returns_zero_when_custom_field_item_quantity_and_value_empty()
    {
        CustomField::factory()->create([
            CustomField::coreKeysId => Constants::itemQty,
        ]);

        $item = Item::factory()->create([
            Item::isSoldOut => 1,
        ]);

        $reflection = new \ReflectionClass($this->psInfoService);
        $method = $reflection->getMethod('validatedData');
        $method->setAccessible(true);

        $result = $method->invoke($this->psInfoService, '', Constants::itemQty, $item->{Item::id}, null, null);

        $this->assertEquals(0, $result);
    }

    public function test_validated_data_change_item_is_sold_out_when_custom_field_item_quantity_and_value_greater_than_zero()
    {
        CustomField::factory()->create([
            CustomField::coreKeysId => Constants::itemQty,

        ]);
        $item = Item::factory()->create([
            Item::isSoldOut => 1,
        ]);

        $reflection = new \ReflectionClass($this->psInfoService);
        $method = $reflection->getMethod('validatedData');
        $method->setAccessible(true);

        $result = $method->invoke($this->psInfoService, 2, Constants::itemQty, $item->{Item::id}, null, null);

        $this->assertEquals(2, $result);
        $this->assertDatabaseHas(Item::tableName, [
            Item::id => $item->id,
            Item::isSoldOut => 0,
        ]);
    }

    public function test_validated_data_when_value_false_returns_zero()
    {
        $reflection = new \ReflectionClass($this->psInfoService);
        $method = $reflection->getMethod('validatedData');
        $method->setAccessible(true);

        $result = $method->invoke($this->psInfoService, false, null, null, null, null);

        $this->assertEquals(0, $result);
    }

    public function test_validated_data_when_value_is_image_returns_image_file()
    {
        $item = Item::factory()->create();
        $itemInfo = ItemInfo::factory()->create([
            ItemInfo::itemId => $item->{Item::id},
        ]);

        $fakeImage = UploadedFile::fake()->image('test-image.jpg');

        $reflection = new \ReflectionClass($this->psInfoService);
        $method = $reflection->getMethod('validatedData');
        $method->setAccessible(true);

        $result = $method->invoke(
            $this->psInfoService,
            $fakeImage,
            $itemInfo->{ItemInfo::coreKeysId},
            $item->{Item::id},
            ItemInfo::class,
            ItemInfo::itemId
        );

        try {
            $this->assertIsString($result);
            $this->assertStringEndsWith('_city.jpg', $result);
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+_city\.jpg$/', $result);
        } finally {
            // File Clean Up
            $paths = [
                public_path(Constants::storageOriginalPath.$result),
                public_path(Constants::storageThumb1xPath.$result),
                public_path(Constants::storageThumb2xPath.$result),
                public_path(Constants::storageThumb3xPath.$result),
            ];
            foreach ($paths as $p) {
                if (file_exists($p)) {
                    @unlink($p);
                }
            }
        }
    }

    public function test_validated_data_when_value_is_not_file_returns_value()
    {
        $item = Item::factory()->create();
        $itemInfo = ItemInfo::factory()->create([
            ItemInfo::itemId => $item->{Item::id},
        ]);

        $reflection = new \ReflectionClass($this->psInfoService);
        $method = $reflection->getMethod('validatedData');
        $method->setAccessible(true);

        $inputValue = 'test';

        $result = $method->invoke(
            $this->psInfoService,
            $inputValue,
            $itemInfo->{ItemInfo::coreKeysId},
            $item->{Item::id},
            ItemInfo::class,
            ItemInfo::itemId
        );

        $this->assertEquals($inputValue, $result);
    }

    public function test_validated_data_when_value_is_null_returns_null()
    {
        $item = Item::factory()->create();
        $itemInfo = ItemInfo::factory()->create([
            ItemInfo::itemId => $item->{Item::id},
        ]);

        $reflection = new \ReflectionClass($this->psInfoService);
        $method = $reflection->getMethod('validatedData');
        $method->setAccessible(true);

        $result = $method->invoke(
            $this->psInfoService,
            null,
            $itemInfo->{ItemInfo::coreKeysId},
            $item->{Item::id},
            ItemInfo::class,
            ItemInfo::itemId
        );

        $this->assertNull($result);
    }
    // endregion
}
