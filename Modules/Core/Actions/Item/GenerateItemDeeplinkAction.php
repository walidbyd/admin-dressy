<?php

namespace Modules\Core\Actions\Item;

use App\Config\ps_constant;
use App\Http\Contracts\Image\ImageServiceInterface;
use App\Http\Contracts\Utilities\DynamicLinkServiceInterface;
use Modules\Core\Entities\CoreImage;
use Modules\Core\Entities\Item\Item;
use Modules\Core\Http\Services\Item\ItemService;
use Throwable;

class GenerateItemDeeplinkAction
{
    public function __construct(
        protected ItemService $itemService,
        protected ImageServiceInterface $imageService,
        protected DynamicLinkServiceInterface $dynamicLinkService,
    ) {}

    public function handle($itemId)
    {
        try {

            // Validate
            if (empty($itemId)) {
                throw new \InvalidArgumentException("ItemId can't be empty.");
            }

            $deepLinkServiceProvider = $this->dynamicLinkService->getDeepLinkServiceProvider();

            // Get the Item
            $item = $this->itemService->get($itemId);

            // Generate
            if ($deepLinkServiceProvider == ps_constant::FIREBASE) {
                return $this->generateDeeplinkWithFirebase($item);
            } elseif ($deepLinkServiceProvider == ps_constant::PSX_DYNAMIC_LINK) {
                return $this->generateDeeplinkWithPSXDynamicLink($item);
            }

            return $item;
        } catch (Throwable $e) {
            throw $e;
        }
    }

    // //////////////////////////////////////////////////////////////////
    // / Private Functions
    // //////////////////////////////////////////////////////////////////
    private function generateDeeplinkWithFirebase($item)
    {
        // Prepare Image
        $conds = [CoreImage::imgParentId => $item->id, CoreImage::imgType => 'item', CoreImage::ordering => 1];
        $image = $this->imageService->get(conds: $conds);
        $img = $image ? $image->img_path : '';

        // Get the Dynamic Link
        $dynamic_link = deeplinkGenerate($item->id, $item->title, $item->description, $img);

        // Update
        return $this->itemService->updateDynamicLink($item, $dynamic_link['msg']);

    }

    private function generateDeeplinkWithPSXDynamicLink($item)
    {
        // Get the Dynamic Link
        $dynamicLink = $this->dynamicLinkService->generateDynamicLinks(
            $item, ['item_id' => Item::id],
            ps_constant::DYNAMIC_LINK_ITEM
        );

        // Update
        if ($dynamicLink != null && $dynamicLink->isNotEmpty()) {
            return $this->itemService->updateDynamicLink($item, $dynamicLink[0]['short_code']);
        } else {
            throw new \Exception('Failed to generate dynamic link.');
        }
    }
}
