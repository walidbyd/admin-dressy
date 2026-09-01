<?php

use App\Http\Contracts\Localization\LanguageImportServiceInterface;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Http\Services\Localization\LanguageImportService;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $languageImportService = app()->make(LanguageImportServiceInterface::class);

        $filepath = base_path('storage/languages/core/1.5.6/backend.csv');
        try {
            $languageImportService->importFromStorage($filepath, LanguageImportService::BACKEND);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
