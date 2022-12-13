<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Unit\Model;

use Magento\Framework\HTTP\PhpEnvironment\Request;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryCatalog\Model\GetStockIdForCurrentWebsite;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for stock id resolver
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetStockIdForCurrentWebsiteTest extends TestCase
{
    /**
     * @var StoreManagerInterface|MockObject
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var StockResolverInterface|MockObject
     */
    private StockResolverInterface $stockResolver;

    /**
     * @var GetStockIdForCurrentWebsite
     */
    private GetStockIdForCurrentWebsite $model;

    /**
     * @var Request|MockObject
     */
    private Request $request;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->stockResolver = $this->createMock(StockResolverInterface::class);
        $this->request = $this->createMock(Request::class);
        $this->model = new GetStockIdForCurrentWebsite($this->storeManager, $this->stockResolver, $this->request);
    }

    /**
     * @return void
     */
    public function testExecute(): void
    {
        $storeId = 1;
        $websiteId = 1;
        $websiteCode = 'website_code';
        $stockId = 2;

        $this->request->expects($this->once())->method('getParam')
            ->with('store')
            ->willReturn($storeId);
        $store = $this->createMock(StoreInterface::class);
        $store->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn($websiteId);
        $website = $this->createMock(WebsiteInterface::class);
        $website->expects($this->once())
            ->method('getCode')
            ->willReturn($websiteCode);
        $stock = $this->createMock(StockInterface::class);
        $stock->expects($this->once())->method('getStockId')
            ->willReturn($stockId);

        $this->storeManager->expects($this->once())
            ->method('getStore')
            ->willReturn($store);
        $this->storeManager->expects($this->once())
            ->method('getWebsite')
            ->with($websiteId)
            ->willReturn($website);
        $this->stockResolver->expects($this->once())
            ->method('execute')
            ->with(SalesChannelInterface::TYPE_WEBSITE, $websiteCode)
            ->willReturn($stock);

        $this->assertSame($stockId, $this->model->execute());
    }
}
