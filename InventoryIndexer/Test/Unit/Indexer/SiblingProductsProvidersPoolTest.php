<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Indexer;

use Magento\InventoryIndexer\Indexer\SiblingProductsProviderInterface;
use Magento\InventoryIndexer\Indexer\SiblingProductsProvidersPool;
use PHPUnit\Framework\TestCase;

class SiblingProductsProvidersPoolTest extends TestCase
{
    public function testGetSiblingsGroupedByTypeEmptyPool(): void
    {
        $model = new SiblingProductsProvidersPool([]);
        self::assertCount(0, $model->getSiblingsGroupedByType([]));
    }

    public function testGetSiblingsGroupedByTypeNoSiblings(): void
    {
        $skus = ['simple-1', 'simple-2'];

        $bundleProductsProvider = $this->createMock(SiblingProductsProviderInterface::class);
        $bundleProductsProvider->expects(self::once())->method('getSkus')->willReturn($skus)->willReturn([]);

        $model = new SiblingProductsProvidersPool(['bundle' => $bundleProductsProvider]);
        self::assertCount(0, $model->getSiblingsGroupedByType($skus));
    }

    public function testGetSiblingsGroupedByType(): void
    {
        $skus = ['simple-1', 'simple-2'];

        $bundleProductsProvider = $this->createMock(SiblingProductsProviderInterface::class);
        $bundleProductsProvider->expects(self::once())->method('getSkus')->willReturn($skus)
            ->willReturn(['bundle-1', 'bundle-2']);
        $groupedProductsProvider = $this->createMock(SiblingProductsProviderInterface::class);
        $groupedProductsProvider->expects(self::once())->method('getSkus')->willReturn($skus)
            ->willReturn(['grouped-1']);

        $model = new SiblingProductsProvidersPool(
            ['bundle' => $bundleProductsProvider, 'grouped' => $groupedProductsProvider]
        );
        $result = $model->getSiblingsGroupedByType($skus);
        self::assertEquals(['bundle' => ['bundle-1', 'bundle-2'], 'grouped' => ['grouped-1']], $result);
    }
}
