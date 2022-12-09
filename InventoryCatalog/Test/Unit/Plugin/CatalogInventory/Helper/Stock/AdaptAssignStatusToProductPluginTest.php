<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Unit\Plugin\CatalogInventory\Helper\Stock;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Helper\Stock;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;
use Magento\InventoryCatalog\Plugin\CatalogInventory\Helper\Stock\AdaptAssignStatusToProductPlugin;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for AdaptAssignStatusToProductPlugin interceptor
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AdaptAssignStatusToProductPluginTest extends TestCase
{
    /**
     * @var GetStockIdForCurrentWebsite|MockObject
     */
    private GetStockIdForCurrentWebsite $getStockIdForCurrentWebsite;

    /**
     * @var AreProductsSalableInterface|MockObject
     */
    private AreProductsSalableInterface $areProductsSalable;

    /**
     * @var DefaultStockProviderInterface|MockObject
     */
    private DefaultStockProviderInterface $defaultStockProvider;

    /**
     * @var GetProductIdsBySkusInterface|MockObject
     */
    private GetProductIdsBySkusInterface $getProductIdsBySkus;

    /**
     * @var AdaptAssignStatusToProductPlugin|MockObject
     */
    private AdaptAssignStatusToProductPlugin $plugin;

    protected function setUp(): void
    {
        $this->getStockIdForCurrentWebsite = $this->createMock(GetStockIdForCurrentWebsite::class);
        $this->areProductsSalable = $this->createMock(AreProductsSalableInterface::class);
        $this->defaultStockProvider = $this->createMock(DefaultStockProviderInterface::class);
        $this->getProductIdsBySkus = $this->createMock(GetProductIdsBySkusInterface::class);

        $this->plugin = new AdaptAssignStatusToProductPlugin(
            $this->getStockIdForCurrentWebsite,
            $this->areProductsSalable,
            $this->defaultStockProvider,
            $this->getProductIdsBySkus
        );
    }

    public function testBeforeAssignStatusToProduct(): void
    {
        $stock = $this->createMock(Stock::class);
        $storeId = 1;
        $stockId = 1;
        $product = $this->createMock(Product::class);
        $product->expects($this->any())
            ->method('getSku')
            ->willReturn('sku');
        $product->expects($this->any())
            ->method('getStoreId')
            ->willReturn($storeId);
        $sellable = $this->createMock(IsProductSalableResultInterface::class);

        $this->getProductIdsBySkus->expects($this->once())
            ->method('execute')
            ->with([$product->getSku()])
            ->willReturn([]);
        $this->getStockIdForCurrentWebsite->expects($this->once())
            ->method('execute')
            ->with($product->getStoreId())
            ->willReturn($stockId);
        $this->areProductsSalable->expects($this->once())
            ->method('execute')
            ->with([$product->getSku()], $stockId)
            ->willReturn([$sellable]);

        $result = $this->plugin->beforeAssignStatusToProduct(
            $stock,
            $product
        );
        $this->assertSame([$product, 0], $result);
    }
}
