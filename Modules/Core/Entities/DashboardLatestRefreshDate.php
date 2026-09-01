<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardLatestRefreshDate extends Model
{
    use HasFactory;

    protected $fillable = ['id', 'from', 'to'];

    protected $table = 'psx_dashboard_latest_refresh_date';

    const CREATED_AT = null;

    const UPDATED_AT = null;

    const from = 'from';

    const to = 'to';

    protected static function newFactory()
    {
        return \Modules\Core\Database\factories\DashboardLatestRefreshDateFactory::new();
    }
}
