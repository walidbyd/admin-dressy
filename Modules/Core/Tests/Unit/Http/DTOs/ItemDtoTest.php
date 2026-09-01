<?php

namespace Modules\Core\Tests\Unit\Http\DTOs;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Modules\Core\DTOs\ItemDto;
use Modules\Core\Http\Requests\Item\StoreItemApiRequest;
use Tests\TestCase;

class ItemDtoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // region from
    // -------------------------------------------------------------------
    // from
    // -------------------------------------------------------------------

    public function test_from_with_all_fields()
    {
        // Create test files
        $videoIcon = UploadedFile::fake()->image('video_icon.jpg');
        $video = UploadedFile::fake()->create('video.mp4', 5000, 'video/mp4');
        $customFieldImage = UploadedFile::fake()->image('custom_image.jpg');

        // Create test data
        $testData = [
            'title' => 'Test Item',
            'description' => 'This is a test item description with more than 10 characters',
            'category_id' => 1,
            'subcategory_id' => 1,
            'location_city_id' => 1,
            'location_township_id' => 1,
            'currency_id' => 1,
            'original_price' => 100.50,
            'percent' => 10,
            'lat' => '16.8409',
            'lng' => '96.1735',
            'search_tag' => 'test,item',
            'ordering' => '1',
            'is_discount' => 1,
            'phone' => '09123456789',
            'login_user_id' => 1,
            'language_symbol' => 'en',
            'images' => ['image1.jpg', 'image2.jpg'],
            'img_caption' => [
                ['name' => 'image1.jpg', 'value' => 'Caption 1'],
                ['name' => 'image2.jpg', 'value' => 'Caption 2'],
            ],
            'img_order' => [
                ['name' => 'image1.jpg', 'order' => 1],
                ['name' => 'image2.jpg', 'order' => 2],
            ],
            'product_relation' => [
                'custom_field_1' => 'Value 1',
                'custom_field_2' => 'Value 2',
                'custom_image_field' => 'custom_image.jpg',
            ],
        ];

        // Create a mock request
        $mockRequest = Mockery::mock(StoreItemApiRequest::class);
        $mockRequest->shouldReceive('validated')->andReturn($testData);
        $mockRequest->shouldReceive('input')->andReturn($testData['product_relation']);
        $mockRequest->shouldReceive('allFiles')->andReturn([
            'video_icon' => $videoIcon,
            'video' => $video,
            'product_relation' => [
                'custom_image_field' => $customFieldImage,
            ],
        ]);
        $mockRequest->shouldReceive('file')
            ->with('video_icon')
            ->andReturn($videoIcon);
        $mockRequest->shouldReceive('file')
            ->with('video')
            ->andReturn($video);
        $mockRequest->shouldReceive('hasFile')
            ->with('video_icon')
            ->andReturn(true);
        $mockRequest->shouldReceive('hasFile')
            ->with('video')
            ->andReturn(true);

        // Execute
        $dto = ItemDto::from($mockRequest);

        $expectedCustomFields = [
            'custom_field_1' => 'Value 1',
            'custom_field_2' => 'Value 2',
            'custom_image_field' => $customFieldImage,
        ];

        // Assertions
        $this->assertInstanceOf(ItemDto::class, $dto);
        $this->assertEquals('Test Item', $dto->title);
        $this->assertEquals(1, $dto->categoryId);
        $this->assertEquals(1, $dto->subcategoryId);
        $this->assertEquals(1, $dto->currencyId);
        $this->assertEquals(1, $dto->locationCityId);
        $this->assertEquals(1, $dto->locationTownshipId);
        $this->assertEquals(100.50, $dto->originalPrice);
        $this->assertEquals(10, $dto->percent);
        $this->assertEquals('16.8409', $dto->lat);
        $this->assertEquals('96.1735', $dto->lng);
        $this->assertEquals('test,item', $dto->searchTag);
        $this->assertEquals('1', $dto->ordering);
        $this->assertEquals(1, $dto->isDiscount);
        $this->assertEquals('09123456789', $dto->phone);
        $this->assertEquals(1, $dto->loginUserId);
        $this->assertEquals('en', $dto->languageSymbol);
        $this->assertEquals(['image1.jpg', 'image2.jpg'], $dto->images);
        $this->assertEquals($videoIcon, $dto->videoIcon);
        $this->assertEquals($video, $dto->video);
        $this->assertEquals($expectedCustomFields, $dto->customFields);
    }

    public function test_from_without_optional_fields()
    {
        // Create test data with only required fields
        $testData = [
            'title' => 'Test Item',
            'category_id' => 1,
            'location_city_id' => 1,
            'original_price' => 100.50,
            'login_user_id' => 1,
        ];

        // Create a mock request
        $mockRequest = Mockery::mock(StoreItemApiRequest::class);
        $mockRequest->shouldReceive('validated')->andReturn($testData);
        $mockRequest->shouldReceive('input')->andReturn($testData['product_relation'] ?? []);
        $mockRequest->shouldReceive('allFiles')->andReturn([]);
        $mockRequest->shouldReceive('file')->andReturn(null);

        // Execute
        $dto = ItemDto::from($mockRequest);

        // Assertions for optional fields
        $this->assertNull($dto->id);
        $this->assertNull($dto->subcategoryId);
        $this->assertNull($dto->currencyId);
        $this->assertNull($dto->locationTownshipId);
        $this->assertNull($dto->shopId);
        $this->assertEmpty($dto->description);
        $this->assertNull($dto->searchTag);
        $this->assertNull($dto->dynamicLink);
        $this->assertEmpty($dto->lat);
        $this->assertEmpty($dto->lng);
        $this->assertNull($dto->status);
        $this->assertNull($dto->isPaid);
        $this->assertEmpty($dto->isSoldOut);
        $this->assertNull($dto->ordering);
        $this->assertEmpty($dto->isDiscount);
        $this->assertEmpty($dto->itemTouchCount);
        $this->assertEmpty($dto->favouriteCount);
        $this->assertEmpty($dto->overallRating);
        $this->assertNull($dto->vendorId);
        $this->assertNull($dto->addedUserId);
        $this->assertNull($dto->updatedUserId);
        $this->assertNull($dto->percent);
        $this->assertNull($dto->phone);
        $this->assertEmpty($dto->imgOrder);
        $this->assertEmpty($dto->imgCaption);
        $this->assertEmpty($dto->languageSymbol);
        $this->assertEmpty($dto->customFields);
        $this->assertEmpty($dto->images);
        $this->assertNull($dto->videoIcon);
        $this->assertNull($dto->video);
    }

    public function test_from_uses_auth_id_when_no_login_user_id_provided()
    {
        Auth::shouldReceive('id')->once()->andReturn(2);

        // Create test data without login_user_id
        $testData = [
            'title' => 'Test Item',
            'description' => 'This is a test item description with more than 10 characters',
            'category_id' => 1,
            'subcategory_id' => 1,
            'location_city_id' => 1,
            'location_township_id' => 1,
            'currency_id' => 1,
            'original_price' => 100.50,
            'percent' => 10,
            'lat' => '16.8409',
            'lng' => '96.1735',
            'search_tag' => 'test,item',
            'ordering' => '1',
            'is_discount' => 1,
            'phone' => '09123456789',
            'language_symbol' => 'en',
            'images' => [],
        ];

        // Create a mock request
        $mockRequest = Mockery::mock(StoreItemApiRequest::class);
        $mockRequest->shouldReceive('validated')->andReturn($testData);
        $mockRequest->shouldReceive('input')->andReturn($testData['product_relation'] ?? []);
        $mockRequest->shouldReceive('allFiles')->andReturn([]);
        $mockRequest->shouldReceive('file')->andReturn(null);

        // Execute
        $dto = ItemDto::from($mockRequest);

        // Assertions
        $this->assertEquals('2', $dto->loginUserId);
    }
    // endregion

    // region copyWith
    // -------------------------------------------------------------------
    // copyWith
    // -------------------------------------------------------------------

    public function test_copy_with()
    {
        // Create initial DTO with sample data
        $originalDto = new ItemDto(
            id: 1,
            title: 'Original Title',
            categoryId: 1,
            subcategoryId: 1,
            currencyId: 1,
            locationCityId: 1,
            locationTownshipId: 1,
            shopId: null,
            price: 100.0,
            originalPrice: '100.0',
            description: 'Original Description',
            searchTag: 'original,tag',
            dynamicLink: null,
            lat: '16.8409',
            lng: '96.1735',
            status: '1',
            isPaid: 0,
            isSoldOut: 0,
            ordering: '1',
            isAvailable: 1,
            isDiscount: '0',
            itemTouchCount: 0,
            favouriteCount: 0,
            overallRating: 0,
            vendorId: null,
            addedUserId: 1,
            updatedUserId: null,
            percent: null,
            phone: '09123456789',
            imgOrder: [],
            imgCaption: [],
            loginUserId: '1',
            languageSymbol: 'en',
            customFields: [
                'field1' => 'value1',
                'field2' => 'value2',
            ],
            images: ['image1.jpg'],
            video: null,
            videoIcon: null
        );

        // Test updating multiple fields
        $updatedDto = $originalDto->copyWith(
            title: 'Updated Title',
            description: 'Updated Description',
            price: 150.0,
            originalPrice: '150.0',
            isDiscount: '1',
            vendorId: 5,
            customFields: [
                'field1' => 'new_value1',
                'field3' => 'value3',
            ],
            images: ['image1.jpg', 'image2.jpg']
        );

        // Assert updated fields
        $this->assertEquals('Updated Title', $updatedDto->title);
        $this->assertEquals('Updated Description', $updatedDto->description);
        $this->assertEquals(150.0, $updatedDto->price);
        $this->assertEquals('150.0', $updatedDto->originalPrice);
        $this->assertEquals('1', $updatedDto->isDiscount);
        $this->assertEquals(5, $updatedDto->vendorId);
        $this->assertEquals([
            'field1' => 'new_value1',
            'field3' => 'value3',
        ], $updatedDto->customFields);
        $this->assertEquals(['image1.jpg', 'image2.jpg'], $updatedDto->images);

        // Assert unchanged fields
        $this->assertEquals(1, $updatedDto->id);
        $this->assertEquals(1, $updatedDto->categoryId);
        $this->assertEquals(1, $updatedDto->subcategoryId);
        $this->assertEquals(1, $updatedDto->currencyId);
        $this->assertEquals('09123456789', $updatedDto->phone);
        $this->assertEquals('en', $updatedDto->languageSymbol);

        // Test updating single field
        $singleUpdateDto = $originalDto->copyWith(
            price: 200.0
        );
        $this->assertEquals(200.0, $singleUpdateDto->price);
        $this->assertEquals('Original Title', $singleUpdateDto->title); // Should remain unchanged

        // Test updating with null values (should keep original values)
        $nullUpdateDto = $originalDto->copyWith(
            title: null,
            description: null
        );
        $this->assertEquals('Original Title', $nullUpdateDto->title);
        $this->assertEquals('Original Description', $nullUpdateDto->description);

        // Test updating files
        $video = UploadedFile::fake()->create('video.mp4');
        $videoIcon = UploadedFile::fake()->image('icon.jpg');
        $fileUpdatedDto = $originalDto->copyWith(
            video: $video,
            videoIcon: $videoIcon
        );
        $this->assertEquals($video, $fileUpdatedDto->video);
        $this->assertEquals($videoIcon, $fileUpdatedDto->videoIcon);
        $this->assertEquals(['image1.jpg'], $fileUpdatedDto->images); // Images should remain unchanged

        // Test partial custom fields update
        $partialCustomFieldsDto = $originalDto->copyWith(
            customFields: ['field2' => 'updated_value2']
        );
        $this->assertEquals([
            'field2' => 'updated_value2',
        ], $partialCustomFieldsDto->customFields);
    }
    // endregion

    // region toArray
    // -------------------------------------------------------------------
    // toArray
    // -------------------------------------------------------------------

    public function test_to_array()
    {
        // Create a sample DTO with all fields populated
        $dto = new ItemDto(
            id: 1,
            title: 'Test Item',
            categoryId: 2,
            subcategoryId: 3,
            currencyId: 4,
            locationCityId: 5,
            locationTownshipId: 6,
            shopId: 7,
            price: 100.50,
            originalPrice: 120.0,
            description: 'Test description',
            searchTag: 'test,tag',
            dynamicLink: 'https://example.com',
            lat: '16.8409',
            lng: '96.1735',
            status: '1',
            isPaid: 1,
            isSoldOut: 0,
            ordering: '2',
            isAvailable: 1,
            isDiscount: '0',
            itemTouchCount: 10,
            favouriteCount: 5,
            overallRating: 4,
            vendorId: 8,
            addedUserId: 9,
            updatedUserId: 10,
            percent: 15.0,
            phone: '09123456789',
            imgOrder: [1, 2, 3],
            imgCaption: ['caption1', 'caption2'],
            loginUserId: '11',
            languageSymbol: 'en',
            customFields: ['field1' => 'value1'],
            images: ['image1.jpg', 'image2.jpg'],
            video: null,
            videoIcon: null
        );

        // Execute the method
        $result = $dto->toArray();

        // Expected array structure
        $expected = [
            'id' => 1,
            'title' => 'Test Item',
            'category_id' => 2,
            'subcategory_id' => 3,
            'currency_id' => 4,
            'location_city_id' => 5,
            'location_township_id' => 6,
            'shop_id' => 7,
            'price' => 100.50,
            'original_price' => 120.0,
            'description' => 'Test description',
            'search_tag' => 'test,tag',
            'dynamic_link' => 'https://example.com',
            'lat' => '16.8409',
            'lng' => '96.1735',
            'status' => '1',
            'is_paid' => 1,
            'is_sold_out' => 0,
            'ordering' => '2',
            'is_available' => 1,
            'is_discount' => 0,
            'item_touch_count' => 10,
            'favourite_count' => 5,
            'overall_rating' => 4,
            'vendor_id' => 8,
            'added_user_id' => 9,
            'updated_user_id' => 10,
            'percent' => 15.0,
            'phone' => '09123456789',
        ];

        // Verify the array structure matches exactly
        $this->assertEquals($expected, $result);

        // Test with null values
        $nullDto = new ItemDto(
            id: null,
            title: 'Null Test',
            categoryId: 1,
            subcategoryId: null,
            currencyId: 1,
            locationCityId: 1,
            locationTownshipId: null,
            shopId: null,
            price: 0.0,
            originalPrice: null,
            description: null,
            searchTag: null,
            dynamicLink: null,
            lat: null,
            lng: null,
            status: null,
            isPaid: null,
            isSoldOut: 0,
            ordering: null,
            isAvailable: 1,
            isDiscount: 0,
            itemTouchCount: 0,
            favouriteCount: 0,
            overallRating: 0,
            vendorId: null,
            addedUserId: null,
            updatedUserId: null,
            percent: null,
            phone: null,
            imgOrder: [],
            imgCaption: [],
            loginUserId: '1',
            languageSymbol: 'en',
            customFields: [],
            images: [],
            video: null,
            videoIcon: null
        );

        $nullResult = $nullDto->toArray();

        $this->assertArrayHasKey('id', $nullResult);
        $this->assertNull($nullResult['id']);
        $this->assertArrayHasKey('subcategory_id', $nullResult);
        $this->assertNull($nullResult['subcategory_id']);
        $this->assertArrayHasKey('description', $nullResult);
        $this->assertNull($nullResult['description']);

        // Verify all expected keys exist even with null values
        $this->assertArrayHasKey('dynamic_link', $nullResult);
        $this->assertArrayHasKey('status', $nullResult);
        $this->assertArrayHasKey('is_paid', $nullResult);
    }
    // endregion
}
