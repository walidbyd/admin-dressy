<?php

namespace Tests\Unit\Services\Item;

use InvalidArgumentException;
use Modules\Core\Constants\Constants;
use Modules\Core\Http\Services\Item\ItemSearchStrategyResolver;
use Modules\Core\Http\Services\Item\Strategies\BumpsAndGoogleAdsBetweenStrategy;
use Modules\Core\Http\Services\Item\Strategies\BumpsUpsBetweenStrategy;
use Modules\Core\Http\Services\Item\Strategies\GoogleAdsBetweenStrategy;
use Modules\Core\Http\Services\Item\Strategies\NormalOnlyStrategy;
use Modules\Core\Http\Services\Item\Strategies\PaidFirstStrategy;
use Modules\Core\Http\Services\Item\Strategies\PaidFirstWithGoogleStrategy;
use Modules\Core\Http\Services\Item\Strategies\PaidOnlyStrategy;
use Tests\TestCase;

class ItemSearchStrategyResolverTest extends TestCase
{
    protected ItemSearchStrategyResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new ItemSearchStrategyResolver;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_resolve_returns_correct_strategy_for_each_ad_type()
    {
        $testCases = [
            Constants::onlyPaidItemAdType => PaidOnlyStrategy::class,
            Constants::paidItemFirstAdType => PaidFirstStrategy::class,
            Constants::normalAdsOnlyAdType => NormalOnlyStrategy::class,
            Constants::bumpsUpsBetweenAdType => BumpsUpsBetweenStrategy::class,
            Constants::googleAdsBetweenAdType => GoogleAdsBetweenStrategy::class,
            Constants::paidItemFirstWithGoogleAdType => PaidFirstWithGoogleStrategy::class,
            Constants::bumbsAndGoogleAdsBetweenAdType => BumpsAndGoogleAdsBetweenStrategy::class,
        ];

        foreach ($testCases as $adType => $expectedClass) {
            $strategy = $this->resolver->resolve($adType);
            $this->assertInstanceOf($expectedClass, $strategy);
        }
    }

    public function test_resolve_throws_exception_for_unsupported_ad_type()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported ad post type: invalid_type');

        $this->resolver->resolve('invalid_type');
    }

    public function test_resolve_returns_different_instances_for_same_ad_type()
    {
        $strategy1 = $this->resolver->resolve(Constants::paidItemFirstAdType);
        $strategy2 = $this->resolver->resolve(Constants::paidItemFirstAdType);

        $this->assertInstanceOf(PaidFirstStrategy::class, $strategy1);
        $this->assertInstanceOf(PaidFirstStrategy::class, $strategy2);
        $this->assertNotSame($strategy1, $strategy2, 'Should return new instance each time');
    }
}
