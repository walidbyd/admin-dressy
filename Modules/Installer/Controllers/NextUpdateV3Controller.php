<?php

namespace Modules\Installer\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Modules\Installer\Services\UpdateV3Service;
use Throwable;

class NextUpdateV3Controller extends Controller
{
    public function __construct(
        protected UpdateV3Service $updateV3Service
    ) {}

    public function updateV3View()
    {
        // Handle Zip Upload flash
        $zipUpload = session('zipUploadSuccess');
        if ($zipUpload && now()->greaterThanOrEqualTo($zipUpload['expires_at'])) {
            session()->forget('zipUploadSuccess');
        }

        // Handle Install Update flash
        $install = session('installSuccess');
        if ($install && now()->greaterThanOrEqualTo($install['expires_at'])) {
            session()->forget('installSuccess');
        }

        return view('vendor.installer.update.update-v3');
    }

    public function uploadZip(Request $request)
    {
        try {
            $request->validate([
                'zip_file' => 'required|mimes:zip',
            ]);

            $this->updateV3Service->uploadZip($request->file('zip_file'));

            session([
                'zipUploadSuccess' => [
                    'message' => 'Zip File has been uploaded successfully.',
                    'expires_at' => now()->addSeconds(5),
                ],
            ]);

            return redirect()->route('NextLaravelUpdater::updateV3');
        } catch (Throwable $e) {
            return redirect()->back()->withErrors(['update_error' => $e->getMessage()]);
        }
    }

    public function installUpdate()
    {
        try {
            $this->updateV3Service->backupCode();

            File::deleteDirectory(public_path('build'));

            $this->updateV3Service->extractZip();

            $this->updateV3Service->runMigration();

            $this->updateV3Service->deleteZip();

            $this->updateV3Service->updateVersionCode();

            session()->forget('zipUploadSuccess');
            session([
                'installSuccess' => [
                    'message' => 'Source Code Sync has been finished successfully',
                    'expires_at' => now()->addSeconds(5),
                ],
            ]);

            return redirect()->route('dashboard');
        } catch (Throwable $e) {
            return redirect()->back()->withErrors(['update_error' => $e->getMessage()]);
        }
    }
}
