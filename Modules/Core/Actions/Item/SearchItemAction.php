<?php

namespace Modules\Core\Actions\Item;

use App\Exceptions\PsApiException;
use App\Http\Contracts\Configuration\AdPostTypeServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Constants\Constants;
use Modules\Core\DTOs\Item\SearchItemDto;
use Modules\Core\Http\Services\Item\ItemSearchStrategyResolver;
use Throwable;

class SearchItemAction
{
    public function __construct(
        protected AdPostTypeServiceInterface $adPostTypeService,
        protected ItemSearchStrategyResolver $itemSearchStrategyResolver,
    ) {}

    /**
     * @coveredBy testHandle*
     */
    public function handle(Request $request)
    {
        try {
            $loginUserId = Auth::id() ?? 'nologinuser';

            $itemApiRelation = ['vendor', 'category.categoryLanguageString', 'subcategory', 'city', 'township', 'currency', 'owner', 'itemRelation', 'cover', 'video', 'icon'];

            $adPostType = $this->adPostTypeService->getAdPostType($request->input('ad_post_type'));

            $getAllItemDto = SearchItemDto::from($request, $loginUserId, $itemApiRelation);

            $itemSearchStrategy = $this->itemSearchStrategyResolver->resolve($adPostType);

            $items = $itemSearchStrategy->getAll($getAllItemDto) ?? collect();

            return $items;
        } catch (Throwable $e) {
            throw new PsApiException($e->getMessage().$e->getFile().$e->getLine(), Constants::internalServerErrorStatusCode);
        }
    }
}
