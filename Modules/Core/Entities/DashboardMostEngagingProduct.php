<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DashboardMostEngagingProduct extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected $table = 'psx_dashboard_most_engaging_products';

    const CREATED_AT = 'added_date';

    const UPDATED_AT = 'updated_date';

    const total = 'total';

    const packageId = 'package_id';

    protected static function newFactory()
    {
        return \Modules\Core\Database\factories\DashboardMostEngagingProductFactory::new();
    }
}
