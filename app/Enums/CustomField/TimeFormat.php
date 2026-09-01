<?php

namespace App\Enums\CustomField;

enum TimeFormat: string
{
    case HOUR_12 = '12_HOUR';
    case HOUR_24 = '24_HOUR';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
