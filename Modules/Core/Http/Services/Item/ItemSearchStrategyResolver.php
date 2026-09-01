<?php

namespace Modules\Core\Http\Services\Item;

use InvalidArgumentException;
use Modules\Core\Constants\Constants;
use Modules\Core\Http\Services\Item\Strategies\BumpsAndGoogleAdsBetweenStrategy;
use Modules\Core\Http\Services\Item\Strategies\BumpsUpsBetweenStrategy;
use Modules\Core\Http\Services\Item\Strategies\GoogleAdsBetweenStrategy;
use Modules\Core\Http\Services\Item\Strategies\ItemSearchStrategyInterface;
use Modules\Core\Http\Services\Item\Strategies\NormalOnlyStrategy;
use Modules\Core\Http\Services\Item\Strategies\PaidFirstStrategy;
use Modules\Core\Http\Services\Item\Strategies\PaidFirstWithGoogleStrategy;
use Modules\Core\Http\Services\Item\Strategies\PaidOnlyStrategy;

class ItemSearchStrategyResolver
{
    /**
     * @coveredBy testResolve
     */
    public function resolve(string $adPostType): ItemSearchStrategyInterface
    {
        return match ($adPostType) {
            Constants::onlyPaidItemAdType => app(PaidOnlyStrategy::class),
            Constants::paidItemFirstAdType => app(PaidFirstStrategy::class),
            Constants::normalAdsOnlyAdType => app(NormalOnlyStrategy::class),
            Constants::bumpsUpsBetweenAdType => app(BumpsUpsBetweenStrategy::class),
            Constants::googleAdsBetweenAdType => app(GoogleAdsBetweenStrategy::class),
            Constants::paidItemFirstWithGoogleAdType => app(PaidFirstWithGoogleStrategy::class),
            Constants::bumbsAndGoogleAdsBetweenAdType => app(BumpsAndGoogleAdsBetweenStrategy::class),
            default => throw new InvalidArgumentException("Unsupported ad post type: {$adPostType}"),
        };
    }
}
