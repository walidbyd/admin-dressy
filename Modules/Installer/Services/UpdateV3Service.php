<?php

namespace Modules\Installer\Services;

use App\Config\Cache\CheckVersionUpdateCache;
use App\Helpers\PsArtisanHelper;
use App\Http\Contracts\Localization\BeLanguageStringServiceInterface;
use App\Http\Contracts\Localization\FeLanguageStringServiceInterface;
use App\Http\Contracts\Localization\VendorLanguageStringServiceInterface;
use App\Http\Services\PsService;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\Utilities\CheckVersionUpdate;
use Modules\Core\Http\Facades\PsCache;
use Throwable;
use ZipArchive;

class UpdateV3Service extends PsService
{
    protected $zipFileDir;

    protected $zipFileName;

    protected $zipFilePath;

    public function __construct(
        protected BeLanguageStringServiceInterface $beLanguageStringService,
        protected FeLanguageStringServiceInterface $feLanguageStringService,
        protected VendorLanguageStringServiceInterface $vendorLanguageStringService
    ) {
        $this->zipFileDir = Constants::versionUpdateV3ZipDirectory;
        $this->zipFileName = Constants::versionUpdateV3ZipFileName;
        $this->zipFilePath = public_path("{$this->zipFileDir}/{$this->zipFileName}");
    }

    public function backupCode()
    {
        try {
            if (function_exists('proc_open')) {
                PsArtisanHelper::runArtisanCommandWithPhpVersion(config('app.php_path'), 'artisan backup:run --disable-notifications');
            } else {
                Artisan::call('backup:run --disable-notifications');
            }
        } catch (Throwable $e) {
            if (function_exists('proc_open')) {
                PsArtisanHelper::runArtisanCommandWithPhpVersion(config('app.php_path'), 'artisan optimize:clear');
            } else {
                Artisan::call('optimize:clear');
            }

            throw new Exception('Source Code Back Up Failed. Reason: '.$e->getMessage());
        }
    }

    public function uploadZip(UploadedFile $zipFile)
    {
        try {
            if (! File::exists($this->zipFileDir)) {
                File::makeDirectory($this->zipFileDir, 0755, true);
            }

            $zipFile->move($this->zipFileDir, $this->zipFileName);
        } catch (Throwable $e) {
            throw new Exception('Uploading Zip File Failed. Reason: '.$e->getMessage());
        }
    }

    public function deleteZip()
    {
        try {
            File::delete($this->zipFilePath);
        } catch (Throwable $e) {
            throw new Exception('Deleting Zip File Failed. Reason: '.$e->getMessage());
        }
    }

    public function extractZip()
    {
        try {
            $zip = new ZipArchive;
            if ($zip->open($this->zipFilePath) !== true) {
                throw new Exception("Cannot open zip file: {$this->zipFilePath}");
            }

            $zip->extractTo(base_path());
            $zip->close();
        } catch (Throwable $e) {
            throw new Exception('Extracting Zip Failed. Reason: '.$e->getMessage());
        }
    }

    public function runMigration()
    {
        try {
            $composerPath = base_path('./composer.phar');
            if (function_exists('proc_open')) {
                PsArtisanHelper::runArtisanCommandWithPhpVersion(config('app.php_path'), "$composerPath update");
                PsArtisanHelper::runArtisanCommandWithPhpVersion(config('app.php_path'), 'artisan migrate --force');
            } else {
                Artisan::call('migrate', ['--force' => true]);
            }
        } catch (Throwable $e) {
            throw new Exception('Running Migration Failed. Reason: '.$e->getMessage());
        }
    }

    public function updateVersionCode()
    {
        try {
            $versionFile = storage_path('version.json');

            if (!file_exists($versionFile)) {
                return;
            }

            $jsonData = json_decode(file_get_contents($versionFile), true);
            $versionNumber = $jsonData['versionNumber'] ?? null;
            $versionCode = $jsonData['versionCode'] ?? null;

            if (!$versionCode || !$versionNumber) {
                return;
            }

            $checkVersionUpdate = CheckVersionUpdate::first() ?? new CheckVersionUpdate();

            $fields = [
                'backend_language',
                'frontend_language',
                'mobile_language',
                'vendor_language',
                'field_table',
                'source_code'
            ];

            foreach ($fields as $field) {
                $checkVersionUpdate["{$field}_version_number"] = $versionNumber;
                $checkVersionUpdate["{$field}_version_code"] = $versionCode;
            }

            $checkVersionUpdate->save();

            PsCache::clear(CheckVersionUpdateCache::BASE);
        } catch (Throwable $e) {
            throw new Exception('Updating Version Code Failed. Reason: ' . $e->getMessage());
        }
    }
}
