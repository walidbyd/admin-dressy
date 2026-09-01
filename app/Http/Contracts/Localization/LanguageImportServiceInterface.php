<?php

namespace App\Http\Contracts\Localization;

interface LanguageImportServiceInterface
{
    public function importFromStorage(string $filepath, string $platform);
    
    public function import(string $filepath, string $platform);
}
