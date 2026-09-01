<?php

namespace Modules\Core\Actions\Category;

use App\Http\Contracts\Category\CategoryServiceInterface;
use App\Http\Contracts\Category\SubcategoryServiceInterface;
use App\Http\Contracts\Item\ItemServiceInterface;
use Modules\Core\Entities\Category\Subcategory;
use Modules\Core\Entities\Item\Item;

class DeleteCategoryAction
{
    public function __construct(
        protected CategoryServiceInterface $categoryService,
        protected SubcategoryServiceInterface $subcategoryService,
        protected ItemServiceInterface $itemService,
        protected DeleteSubcategoryAction $deleteSubcategoryAction
    ) {}

    public function handle($categoryId)
    {
        try {
            $subcategories = $this->subcategoryService->getAll(conds: [Subcategory::categoryId => $categoryId]);

            foreach ($subcategories as $subcategory) {
                $this->deleteSubcategoryAction->handle($subcategory->id);
            }

            $items = $this->itemService->getAll(filters: [Item::categoryId => $categoryId], noPagination: true);

            foreach ($items as $item) {
                $this->itemService->delete($item->id);
            }

            return $this->categoryService->delete($categoryId);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
