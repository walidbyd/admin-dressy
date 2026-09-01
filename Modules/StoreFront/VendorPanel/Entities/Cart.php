<?php

namespace Modules\StoreFront\VendorPanel\Entities;

use App\Models\PsModel;
use App\Models\User;
use App\Traits\VendorAuthorizationTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Constants\Constants;
use Modules\Core\Entities\Item\CartItem;
use Modules\Core\Entities\Vendor\Vendor;

class Cart extends PsModel
{
    use HasFactory, VendorAuthorizationTrait;

    protected $fillable = ['id', 'user_id', 'vendor_id', 'added_date', 'added_user_id', 'updated_date', 'updated_user_id', 'updated_flag'];

    protected $table = 'psx_carts';

    const CREATED_AT = 'added_date';

    const UPDATED_AT = 'updated_date';

    const tableName = 'psx_carts';

    const userId = 'user_id';

    const id = 'id';

    const vendorId = 'vendor_id';

    const addedDate = 'added_date';

    public function __construct()
    {
        $this->vendorModule = Constants::vendorOrderListModule;
    }

    protected static function newFactory()
    {
        // return \Modules\Core\Database\factories\CategoryFactory::new();
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'added_user_id');
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class, 'cart_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_user_id');
    }
}
