<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Icon extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected $table = 'psx_icons';

    protected static function newFactory()
    {
        return \Modules\Core\Database\factories\IconFactory::new();
    }
}
