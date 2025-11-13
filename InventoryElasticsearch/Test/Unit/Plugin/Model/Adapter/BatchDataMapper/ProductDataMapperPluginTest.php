<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryElasticsearch\Test\Unit\Plugin\Model\Adapter\BatchDataMapper;

use Magento\Elasticsearch\Model\Adapter\BatchDataMapper\ProductDataMapper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryCatalog\Model\GetSkusByProductIds;
use Magento\InventoryElasticsearch\Plugin\Model\Adapter\BatchDataMapper\ProductDataMapperPlugin;
use Magento\InventorySalesApi\Model\GetStockItemsDataInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for additional field product data mapper plugin
 */
class ProductDataMapperPluginTest extends TestCase
{
    /**
     * @var ProductDataMapperPlugin
     */
    private $plugin;

    /**
     * @var StockByWebsiteIdResolverInterface|MockObject
     */
    private $stockByWebsiteIdResolverMock;

    /**
     * @var StoreRepositoryInterface|MockObject
     */
    private $storeRepositoryMock;

    /**
     * @var GetStockItemsDataInterface|MockObject
     */
    private $getStockItemsDataMock;

    /**
     * @var ProductDataMapper|MockObject
     */
    private $productDataMapperMock;

    /**
     * @var GetSkusByProductIds|MockObject
     */
    private $getSkusByProductIdsMock;

    /**
     * @inheirtDoc
     */
    protected function setUp(): void
    {
        $this->stockByWebsiteIdResolverMock = $this->createMock(StockByWebsiteIdResolverInterface::class);
        $this->storeRepositoryMock = $this->createMock(StoreRepositoryInterface::class);
        $this->getStockItemsDataMock = $this->createMock(GetStockItemsDataInterface::class);
        $this->productDataMapperMock = $this->createMock(ProductDataMapper::class);
        $this->getSkusByProductIdsMock = $this->createMock(GetSkusByProductIds::class);
        $this->plugin = new ProductDataMapperPlugin(
            $this->stockByWebsiteIdResolverMock,
            $this->storeRepositoryMock,
            $this->getStockItemsDataMock,
            $this->getSkusByProductIdsMock
        );
    }

    /**
     * Test for `afterMap` when additional product data mapper attribute added
     *
     * @dataProvider stockDataProvider
     * @param int $storeId
     * @param int $websiteId
     * @param int $stockId
     * @param int $salability
     * @return void
     * @throws NoSuchEntityException|LocalizedException
     */
    public function testAfterMap(int $storeId, int $websiteId, int $stockId, int $salability): void
    {
        $productId = 123;
        $sku = '24-MB01';
        $attribute = ['is_out_of_stock' => (int)!$salability];
        $documents = [
            $productId => [
                'store_id' => $storeId,
                'status' => 1,
            ],
        ];
        $expectedResult = [$productId => array_merge($documents[$productId], $attribute)];

        $storeMock = $this->createMock(StoreInterface::class);
        $this->storeRepositoryMock->expects(self::once())
            ->method('getById')
            ->with($storeId)
            ->willReturn($storeMock);
        $storeMock->expects(self::atLeastOnce())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $stock = $this->createMock(StockInterface::class);
        $stock->expects(self::atLeastOnce())
            ->method('getStockId')
            ->willReturn($stockId);
        $this->stockByWebsiteIdResolverMock->expects(self::once())
            ->method('execute')
            ->with($websiteId)
            ->willReturn($stock);
        $this->getSkusByProductIdsMock->expects(self::once())
            ->method('execute')
            ->with([$productId])
            ->willReturn([$productId => $sku]);
        $this->getStockItemsDataMock->expects(self::once())
            ->method('execute')
            ->with([$sku], $stockId)
            ->willReturn([$sku => ['is_salable' => $salability]]);

        $this->assertSame(
            $expectedResult,
            $this->plugin->afterMap($this->productDataMapperMock, $documents, [], $storeId)
        );
    }

    /**
     * @return array
     */
    public static function stockDataProvider(): array
    {
        return [
            ['storeId' => 1, 'websiteId' => 1, 'stockId' => 1, 'salability' => 1],
            ['storeId' => 2, 'websiteId' => 20, 'stockId' => 45, 'salability' => 0],
        ];
    }
}
