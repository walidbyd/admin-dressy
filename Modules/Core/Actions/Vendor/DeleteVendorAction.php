<?php

namespace Modules\Core\Actions\Vendor;

use App\Http\Contracts\Item\ItemServiceInterface;
use Modules\Core\Entities\Item\Item;
use Modules\Core\Http\Services\Vendor\VendorService;

class DeleteVendorAction
{
    public function __construct(
        protected VendorService $vendorService,
        protected ItemServiceInterface $itemService
    ) {}

    public function handle($id)
    {
        try {
            $items = $this->itemService->getAll(filters: [Item::vendorId => $id], noPagination: true);

            foreach ($items as $item) {
                $this->itemService->delete($item->{Item::id});
            }

            return $this->vendorService->delete($id);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
