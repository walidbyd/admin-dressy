<?php

namespace Modules\Core\Tests\Unit\Http\Services\Image;

use App\Http\Contracts\Image\ImageProcessingServiceInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image as ImageFacade;
use Intervention\Image\Image as InterventionImage;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use Modules\Core\DTOs\ItemDto;
use Modules\Core\Entities\CoreImage;
use Modules\Core\Http\Services\Image\ImageService;
use Tests\TestCase;

class ImageServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected $imageMock;

    protected $imageProcessingService;

    protected $imageService;

    protected function setup(): void
    {
        parent::setUp();

        $this->imageMock = Mockery::mock(InterventionImage::class);

        $this->imageProcessingService = Mockery::mock(ImageProcessingServiceInterface::class);

        $this->imageService = new ImageService(
            $this->imageProcessingService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // region saveDropzoneMultiImageLogic
    // -------------------------------------------------------------------
    // saveDropzoneMultiImageLogic
    // -------------------------------------------------------------------

    public function test_save_dropzone_multi_image()
    {
        $image1 = 'test1.jpg';
        $image2 = 'test2.jpg';

        $itemDto = $this->createItemDto([
            'images' => [$image1, $image2],
            'imgCaption' => [
                ['name' => $image1, 'value' => 'Caption 1'],
                ['name' => $image2, 'value' => 'Caption 2'],
            ],
            'imgOrder' => [
                ['name' => $image1, 'order' => 1],
                ['name' => $image2, 'order' => 2],
            ],
        ]);

        $imageService = Mockery::mock(ImageService::class, [$this->imageProcessingService])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        ImageFacade::shouldReceive('make')
            ->andReturn(InterventionImage::class);

        $imageService->shouldReceive('save')
            ->twice()
            ->andReturnNull();

        File::shouldReceive('exists')
            ->andReturn(true);
        File::shouldReceive('delete')
            ->andReturn(true);

        $result = $imageService->saveDropzoneMultiImage([], 123, $itemDto);
        $this->assertNull($result);
    }
    // endregion

    // region save
    // -------------------------------------------------------------------
    // save
    // -------------------------------------------------------------------

    public function test_save_throws_exception_when_file_is_null()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->imageService->save(null, ['key' => 'value']);
    }

    public function test_save_throws_exception_when_image_data_does_not_contain_required_keys()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->imageService->save('file', ['key' => 'value']);
    }

    public function test_save()
    {
        $file = collect([UploadedFile::fake()->image('test.jpg')]);
        $imgData = [
            CoreImage::imgParentId => 1,
            CoreImage::imgType => 'item',
            CoreImage::ordering => 1,
        ];
        $extension = 'jpg';

        Auth::shouldReceive('id')->once()->andReturn(123);

        $this->imageProcessingService->shouldReceive('createImageFiles')
            ->once()
            ->andReturn([$this->imageMock]);

        $this->imageMock->shouldReceive('width')->andReturn(800);
        $this->imageMock->shouldReceive('height')->andReturn(600);

        $result = $this->imageService->save($file, $imgData, $extension);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{13}_\.jpg$/', $result);
        $this->assertDatabaseHas(CoreImage::class, [
            CoreImage::imgParentId => 1,
            CoreImage::imgType => 'item',
            CoreImage::imgWidth => 800,
            CoreImage::imgHeight => 600,
        ]);
    }
    // endregion

    // region validateExtension
    // -------------------------------------------------------------------
    // validateExtension
    // -------------------------------------------------------------------

    public function test_validate_extension_throws_exception_when_provided_invalid_extension()
    {
        $this->expectException(InvalidArgumentException::class);

        $reflection = new \ReflectionClass($this->imageService);
        $method = $reflection->getMethod('validateExtension');
        $method->setAccessible(true);

        $method->invoke($this->imageService, 'invalid');
    }
    // endregion

    // region saveFile
    // -------------------------------------------------------------------
    // saveFile
    // -------------------------------------------------------------------

    public function test_save_file_with_png_extension_returns_updated_img_data()
    {
        $file = collect([$this->imageMock]);
        $fileName = 'test_image.png';
        $extension = 'png';
        $imgData = ['img_type' => 'item'];

        // From private function createImages
        $this->imageMock->shouldReceive('width')->andReturn(800);
        $this->imageMock->shouldReceive('height')->andReturn(600);

        $rtnImages = [$this->imageMock];
        $this->imageProcessingService
            ->shouldReceive('createImageFiles')
            ->with($file, $fileName, 'item', ['original', '3x', '2x', '1x'])
            ->andReturn($rtnImages);

        $reflection = new \ReflectionClass($this->imageService);
        $method = $reflection->getMethod('saveFile');
        $method->setAccessible(true);

        $result = $method->invoke(
            $this->imageService,
            $file,
            $fileName,
            $extension,
            $imgData
        );

        $this->assertEquals(800, $result['img_width']);
        $this->assertEquals(600, $result['img_height']);
    }

    public function test_save_file_with_ico_extension_returns_updated_img_data()
    {
        $file = collect([$this->imageMock]);
        $fileName = 'icon.ico';
        $extension = 'ico';
        $imgData = ['img_type' => 'item'];

        // From private function createImages
        $this->imageMock->shouldReceive('width')->andReturn(256);
        $this->imageMock->shouldReceive('height')->andReturn(256);

        $this->imageProcessingService
            ->shouldReceive('createIcoFile')
            ->with($file, $fileName)
            ->once();

        $reflection = new \ReflectionClass($this->imageService);
        $method = $reflection->getMethod('saveFile');
        $method->setAccessible(true);

        $result = $method->invoke(
            $this->imageService,
            $file,
            $fileName,
            $extension,
            $imgData
        );

        $this->assertEquals(256, $result['img_width']);
        $this->assertEquals(256, $result['img_height']);
    }
    // endregion

    // region saveOrUpdateImgObj
    // -------------------------------------------------------------------
    // saveOrUpdateImgObj
    // -------------------------------------------------------------------
    public function test_save_or_update_img_obj_when_image_does_not_exist_sets_added_user_id_and_saves()
    {
        /** @var CoreImage|MockInterface $coreImage */
        $coreImage = Mockery::mock(CoreImage::class)->makePartial();
        $coreImage->exists = false;
        $coreImage->img_parent_id = null;
        $coreImage->img_type = null;
        $coreImage->img_width = null;
        $coreImage->img_height = null;
        $coreImage->ordering = null;
        $coreImage->img_desc = null;

        $imgData = [
            'img_width' => 800,
            'img_height' => 600,
            'img_desc' => 'Sample description',
        ];
        $fileName = 'test.jpg';

        Auth::shouldReceive('id')->once()->andReturn(123);

        $coreImage->shouldReceive('fill')->once()->with(Mockery::on(function ($data) use ($fileName) {
            return $data['img_path'] === $fileName
                && $data['img_width'] === 800
                && $data['img_height'] === 600
                && $data['img_desc'] === 'Sample description';
        }))->andReturnSelf();
        $coreImage->shouldReceive('save')->once();

        $reflection = new \ReflectionClass($this->imageService);
        $method = $reflection->getMethod('saveOrUpdateImgObj');
        $method->setAccessible(true);

        $method->invoke($this->imageService, $coreImage, $imgData, $fileName);

        $this->assertEquals(123, $coreImage->added_user_id);
    }

    public function test_save_or_update_img_obj_when_image_exists_sets_updated_user_id_and_saves()
    {
        /** @var CoreImage|MockInterface $coreImage */
        $coreImage = Mockery::mock(CoreImage::class)->makePartial();
        $coreImage->exists = true;
        $coreImage->img_parent_id = 5;
        $coreImage->img_type = 'item';
        $coreImage->img_width = 300;
        $coreImage->img_height = 200;
        $coreImage->ordering = 1;
        $coreImage->img_desc = 'Old desc';

        $imgData = [
            'img_desc' => 'New description',
        ];
        $fileName = 'update.jpg';

        Auth::shouldReceive('id')->once()->andReturn(456);

        $coreImage->shouldReceive('fill')->once()->with(Mockery::on(function ($data) use ($fileName) {
            return $data['img_path'] === $fileName
                && $data['img_desc'] === 'New description';
        }))->andReturnSelf();
        $coreImage->shouldReceive('save')->once();

        $reflection = new \ReflectionClass($this->imageService);
        $method = $reflection->getMethod('saveOrUpdateImgObj');
        $method->setAccessible(true);

        $method->invoke($this->imageService, $coreImage, $imgData, $fileName);

        $this->assertEquals(456, $coreImage->updated_user_id);
    }
    // endregion

    private function createItemDto(array $overrides = [])
    {
        $defaults = [
            'id' => null,
            'title' => 'Test Title',
            'categoryId' => 1,
            'subcategoryId' => null,
            'currencyId' => null,
            'locationCityId' => 1,
            'locationTownshipId' => null,
            'shopId' => null,
            'price' => 100.50,
            'originalPrice' => null,
            'description' => null,
            'searchTag' => null,
            'dynamicLink' => null,
            'lat' => null,
            'lng' => null,
            'status' => null,
            'isPaid' => null,
            'isSoldOut' => 1,
            'ordering' => null,
            'isAvailable' => 1,
            'isDiscount' => 1,
            'itemTouchCount' => 1,
            'favouriteCount' => 1,
            'overallRating' => 0,
            'vendorId' => null,
            'addedUserId' => null,
            'updatedUserId' => null,
            'percent' => null,
            'phone' => null,
            'imgOrder' => null,
            'imgCaption' => null,
            'loginUserId' => 1,
            'languageSymbol' => 'en',
            'customFields' => [],
            'images' => [],
        ];

        $data = array_merge($defaults, $overrides);

        return new ItemDto(
            id: $data['id'],
            title: $data['title'],
            categoryId: $data['categoryId'],
            subcategoryId: $data['subcategoryId'],
            currencyId: $data['currencyId'],
            locationCityId: $data['locationCityId'],
            locationTownshipId: $data['locationTownshipId'],
            shopId: $data['shopId'],
            price: $data['price'],
            originalPrice: $data['originalPrice'],
            description: $data['description'],
            searchTag: $data['searchTag'],
            dynamicLink: $data['dynamicLink'],
            lat: $data['lat'],
            lng: $data['lng'],
            status: $data['status'],
            isPaid: $data['isPaid'],
            isSoldOut: $data['isSoldOut'],
            ordering: $data['ordering'],
            isAvailable: $data['isAvailable'],
            isDiscount: $data['isDiscount'],
            itemTouchCount: $data['itemTouchCount'],
            favouriteCount: $data['favouriteCount'],
            overallRating: $data['overallRating'],
            vendorId: $data['vendorId'],
            addedUserId: $data['addedUserId'],
            updatedUserId: $data['updatedUserId'],
            percent: $data['percent'],
            phone: $data['phone'],
            imgOrder: $data['imgOrder'],
            imgCaption: $data['imgCaption'],
            loginUserId: $data['loginUserId'],
            languageSymbol: $data['languageSymbol'],
            customFields: $data['customFields'],
            images: $data['images']
        );
    }
}
