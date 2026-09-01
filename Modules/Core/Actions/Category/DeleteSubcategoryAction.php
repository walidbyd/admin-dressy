<?php

namespace Modules\Core\Actions\Category;

use App\Http\Contracts\Category\SubcategoryServiceInterface;
use App\Http\Contracts\Item\ItemServiceInterface;
use Modules\Core\Entities\Item\Item;

class DeleteSubcategoryAction
{
    public function __construct(
        protected SubcategoryServiceInterface $subcategoryService,
        protected ItemServiceInterface $itemService
    ) {}

    public function handle($subcategoryId)
    {
        try {
            $items = $this->itemService->getAll(filters: [Item::subCategoryId => $subcategoryId], noPagination: true);

            foreach ($items as $item) {
                $this->itemService->delete($item->id);
            }

            return $this->subcategoryService->delete($subcategoryId);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
