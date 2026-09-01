<?php

namespace Modules\Core\Http\Services\Localization;

use App\Config\ps_constant;
use App\Http\Contracts\Localization\LanguageImportServiceInterface;
use App\Http\Contracts\Localization\LanguageServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Core\Entities\Localization\FeLanguageString;
use Modules\Core\Entities\Localization\Language;
use Modules\Core\Entities\Localization\LanguageString;
use Modules\Core\Entities\Localization\VendorLanguageString;
use Modules\Core\Entities\Project;

class LanguageImportService implements LanguageImportServiceInterface
{
    // CSV main columns
    public const KEY = 'key';

    public const ACTION = 'action';

    // Available Platforms
    public const VENDOR = 'vendor';

    public const FRONTEND = 'frontend';

    public const BACKEND = 'backend';

    // Available Actions
    public const SAVE = 'save';

    public const UPDATE = 'update';

    public const DELETE = 'delete';

    // Allowed File Directory under Storage
    public const CORE = 'core';

    public const MPC = 'MPC';

    public const MOC = 'MOC';

    public const REC = 'REC';

    public const CGC = 'CGC';

    // Default language
    private $defaultLang = 'en';

    public function __construct(protected LanguageServiceInterface $languageService) {}

    public function importFromStorage(string $filepath, string $platform)
    {
        $project = Project::first();

        $projectMap = [
            $this::CORE => null,
            $this::MPC  => ps_constant::mpcBaseProjectId,
            $this::MOC  => ps_constant::mocBaseProjectId,
            $this::REC  => ps_constant::recBaseProjectId,
            $this::CGC  => ps_constant::cgcBaseProjectId,
        ];

        foreach ($projectMap as $dirName => $baseProjectId) {
            $segments = explode('/', $filepath);
            if (in_array($dirName, $segments)) {
                if ($baseProjectId === null || $project->base_project_id == $baseProjectId) {
                    $this->import($filepath, $platform);
                }
                break;
            }
        }
    }

    public function import(string $filepath, string $platform)
    {
        if (!file_exists($filepath)) {
            throw new \Exception("CSV file not found: {$filepath}");
        }

        if (($handle = fopen($filepath, 'r')) === false) {
            throw new \Exception("Failed to open CSV file: {$filepath}");
        }

        DB::beginTransaction();

        try {
            $header = null;

            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                if ($header === null) {
                    $header = array_map('trim', $row);
                    continue;
                }

                $rowData = array_combine($header, $row);

                if (!$rowData) {
                    Log::warning('Invalid CSV row: ' . json_encode($row));
                    continue;
                }

                $key = trim($rowData[$this::KEY] ?? '');
                $action = strtolower(trim($rowData[$this::ACTION] ?? ''));

                if (empty($key) || empty($action)) {
                    Log::warning('Skipped row with missing key/action: ' . json_encode($rowData));
                    continue;
                }

                $supportedLanguages = $this->languageService->getAll();

                switch ($action) {
                    case $this::SAVE:
                        foreach ($supportedLanguages as $language) {
                            $modelClass = $this->getModelClass($platform);
                            $existingLanguageString = $modelClass::where([
                                'key' => $key,
                                'language_id' => $language->id,
                            ])->first();
                            if ($existingLanguageString) {
                                continue;
                            }
                            $languageString = new $modelClass();
                            $languageString->language_id = $language->{Language::id};
                            $languageString->key = $key;
                            $languageString->value = trim($rowData[$language->{Language::symbol}] ?? $rowData[$this->defaultLang]);
                            $languageString->added_user_id = 1;
                            // From previous version update language sync (not null in database)
                            if ($platform == $this::BACKEND) {
                                $languageString->is_from_builder = 0;
                            }
                            $languageString->save();
                        }
                        break;

                    case $this::UPDATE:
                        foreach ($supportedLanguages as $language) {
                            $modelClass = $this->getModelClass($platform);
                            $modelClass::updateOrInsert([
                                'language_id' => $language->{Language::id},
                                'key' => $key
                            ], [
                                'value' => trim($rowData[$language->{Language::symbol}] ?? $rowData[$this->defaultLang])
                            ]);
                        }
                        break;

                    case $this::DELETE:
                        foreach ($supportedLanguages as $language) {
                            $modelClass = $this->getModelClass($platform);
                            $languageString = $modelClass::where([
                                'key' => $key,
                                'language_id' => $language->{Language::id}
                            ]);

                            if ($languageString) {
                                $languageString->delete();
                            }
                        }
                        break;

                    default:
                        Log::warning("Unknown action '{$action}' for key '{$key}'");
                        break;
                }
            }

            fclose($handle);
            DB::commit();
        } catch (\Throwable $e) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            DB::rollBack();
            Log::error('Language import failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getModelClass(string $platform)
    {
        return match ($platform) {
            $this::FRONTEND => FeLanguageString::class,
            $this::BACKEND  => LanguageString::class,
            $this::VENDOR   => VendorLanguageString::class,
            default    => throw new \InvalidArgumentException("Unknown platform: {$platform}"),
        };
    }
}
