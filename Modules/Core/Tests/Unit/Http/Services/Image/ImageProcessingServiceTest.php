<?php

namespace Modules\Core\Tests\Unit\Http\Services\Image;

use App\Http\Contracts\Configuration\BackendSettingServiceInterface;
use App\Http\Contracts\Image\WaterMarkServiceInterface;
use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image as ImageFacade;
use Intervention\Image\Image as InterventionImage;
use Mockery;
use Mockery\MockInterface;
use Modules\Core\Constants\Constants;
use Modules\Core\Http\Services\Image\ImageProcessingService;
use Tests\TestCase;

class ImageProcessingServiceTest extends TestCase
{
    protected $imageProcessingService;

    protected $waterMarkService;

    protected $backendSettingService;

    protected $imageMock;

    protected $storageUploadpath = '/storage/'.Constants::folderPath.'/uploads/';

    protected $storageThumb1xPath = '/storage/'.Constants::folderPath.'/thumbnail/';

    protected $storageThumb2xPath = '/storage/'.Constants::folderPath.'/thumbnail2x/';

    protected $storageThumb3xPath = '/storage/'.Constants::folderPath.'/thumbnail3x/';

    protected function setUp(): void
    {
        parent::setUp();

        $this->waterMarkService = Mockery::mock(WaterMarkServiceInterface::class);
        $this->backendSettingService = Mockery::mock(BackendSettingServiceInterface::class);
        $this->imageMock = Mockery::mock(InterventionImage::class);

        $this->app->instance(BackendSettingServiceInterface::class, $this->backendSettingService);

        $this->imageProcessingService = new ImageProcessingService(
            $this->waterMarkService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // region createIcoFile
    // -------------------------------------------------------------------
    // createIcoFile
    // -------------------------------------------------------------------

    public function test_create_ico_file()
    {
        $file = UploadedFile::fake()->image('dummy.ico');
        $date = new DateTimeImmutable;
        $fileName = 'ico_test_'.$date->getTimestamp().'.ico';

        $this->imageProcessingService->createIcoFile($file, $fileName);

        $storageUploadpath = public_path().$this->storageUploadpath;
        $this->assertFileExists($storageUploadpath.$fileName);

        $storageUploadpath = public_path().$this->storageThumb1xPath;
        $this->assertFileExists($storageUploadpath.$fileName);

        $storageUploadpath = public_path().$this->storageThumb2xPath;
        $this->assertFileExists($storageUploadpath.$fileName);

        $storageUploadpath = public_path().$this->storageThumb3xPath;
        $this->assertFileExists($storageUploadpath.$fileName);

        $this->imageProcessingService->deleteImageFile($fileName);
    }
    // endregion

    // region createImageFile
    // -------------------------------------------------------------------
    // createImageFile
    // -------------------------------------------------------------------

    public function test_create_image_file_without_watermark()
    {
        $backendSetting = (object) [
            'landscape_thumb_width' => 200,
            'square_thumb_height' => 200,
            'potrait_thumb_height' => 200,
            'landscape_thumb2x_width' => 400,
            'square_thumb2x_height' => 400,
            'potrait_thumb2x_height' => 400,
            'landscape_thumb3x_width' => 600,
            'square_thumb3x_height' => 600,
            'potrait_thumb3x_height' => 600,
            'landscape_width' => 800,
            'square_height' => 800,
            'potrait_height' => 800,
        ];
        $this->backendSettingService->shouldReceive('get')->andReturn($backendSetting);

        ImageFacade::shouldReceive('make')->andReturn($this->imageMock);

        // From private function createDirectoryIfNotExists
        File::shouldReceive('isDirectory')->andReturn(false);
        File::shouldReceive('makeDirectory')->andReturn(true);

        // From private function resizeImage
        $this->imageMock->shouldReceive('width')->andReturn(1000);
        $this->imageMock->shouldReceive('height')->andReturn(500);
        $this->imageMock->shouldReceive('resize')->andReturnSelf();

        $this->waterMarkService->shouldReceive('isRequireWatermark')->andReturn(false);

        $this->imageMock->shouldReceive('save')->andReturnTrue();

        $file = UploadedFile::fake()->image('dummy.jpg');
        $fileName = 'dummy_test_'.time().'.jpg';
        $imageType = 'test_type';

        $result = $this->imageProcessingService->createImageFile($file, $fileName, $imageType, 'original');

        $this->assertInstanceOf(InterventionImage::class, $result);
    }

    public function test_create_image_file_with_watermark()
    {
        $backendSetting = (object) [
            'landscape_thumb_width' => 200,
            'square_thumb_height' => 200,
            'potrait_thumb_height' => 200,
            'landscape_thumb2x_width' => 400,
            'square_thumb2x_height' => 400,
            'potrait_thumb2x_height' => 400,
            'landscape_thumb3x_width' => 600,
            'square_thumb3x_height' => 600,
            'potrait_thumb3x_height' => 600,
            'landscape_width' => 800,
            'square_height' => 800,
            'potrait_height' => 800,
        ];
        $this->backendSettingService->shouldReceive('get')->andReturn($backendSetting);

        ImageFacade::shouldReceive('make')->andReturn($this->imageMock);

        // From private function createDirectoryIfNotExistsF
        File::shouldReceive('isDirectory')->andReturn(true);

        // From private function resizeImage
        $this->imageMock->shouldReceive('width')->andReturn(1000);
        $this->imageMock->shouldReceive('height')->andReturn(500);
        $this->imageMock->shouldReceive('resize')->andReturnSelf();

        $this->waterMarkService->shouldReceive('isRequireWatermark')->with('item')->andReturn(true);
        $this->waterMarkService
            ->shouldReceive('applyWatermark')
            ->once()
            ->with($this->imageMock)
            ->andReturn($this->imageMock);

        $this->imageMock->shouldReceive('save')->andReturnTrue();

        $file = UploadedFile::fake()->image('watermarked.jpg');
        $fileName = 'watermark_'.time().'.jpg';
        $imageType = 'item';

        $result = $this->imageProcessingService->createImageFile($file, $fileName, $imageType, 'original');

        $this->assertInstanceOf(InterventionImage::class, $result);
    }
    // endregion

    // region createImageFiles
    // -------------------------------------------------------------------
    // createImageFiles
    // -------------------------------------------------------------------

    public function test_create_image_files_calls_create_image_file_for_each_resolution()
    {
        $file = UploadedFile::fake()->image('multi.jpg');
        $fileName = 'multi_test_'.time().'.jpg';
        $imageType = 'item';
        $resolutions = ['original', '1x', '2x', '3x'];

        /** @var ImageProcessingService|MockInterface $partialImageProcessingServiceMock */
        $partialImageProcessingServiceMock = Mockery::mock(
            ImageProcessingService::class.'[createImageFile]',
            [$this->waterMarkService]
        );

        $partialImageProcessingServiceMock->shouldAllowMockingProtectedMethods()
            ->shouldReceive('createImageFile')
            ->times(count($resolutions))
            ->with($file, $fileName, $imageType, Mockery::any())
            ->andReturn($this->imageMock);

        $result = $partialImageProcessingServiceMock->createImageFiles($file, $fileName, $imageType, $resolutions);

        $this->assertIsArray($result);
        $this->assertCount(count($resolutions), $result);
        foreach ($result as $img) {
            $this->assertInstanceOf(InterventionImage::class, $img);
        }
    }
    // endregion
}
