<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryElasticsearch\Test\Unit\Plugin\Model\Adapter\BatchDataMapper;

use Magento\Elasticsearch\Model\Adapter\BatchDataMapper\ProductDataMapper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryElasticsearch\Plugin\Model\Adapter\BatchDataMapper\ProductDataMapperPlugin;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
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
     * @var GetStockItemDataInterface|MockObject
     */
    private $getStockItemDataMock;

    /**
     * @var ProductDataMapper|MockObject
     */
    private $productDataMapperMock;

    /**
     * @inheirtDoc
     */
    protected function setUp(): void
    {
        $this->stockByWebsiteIdResolverMock = $this->getMockForAbstractClass(StockByWebsiteIdResolverInterface::class);
        $this->storeRepositoryMock = $this->getMockBuilder(StoreRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getById'])
            ->addMethods(['getWebsiteId'])
            ->getMockForAbstractClass();
        $this->getStockItemDataMock = $this->createMock(GetStockItemDataInterface::class);
        $this->productDataMapperMock = $this->createMock(ProductDataMapper::class);
        $this->plugin = (new ObjectManager($this))->getObject(
            ProductDataMapperPlugin::class,
            [
                'stockByWebsiteIdResolver' => $this->stockByWebsiteIdResolverMock,
                'storeRepository' => $this->storeRepositoryMock,
                'getStockItemData' => $this->getStockItemDataMock
            ]
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
        $sku = '24-MB01';
        $attribute = ['is_out_of_stock' => $salability];
        $documents = [
            1 => [
                'store_id' => $storeId,
                'sku' => $sku,
                'status' => $salability
            ],
        ];
        $expectedResult[1] = array_merge($documents[1], $attribute);

        $this->storeRepositoryMock
            ->expects($this->once())
            ->method('getById')
            ->with($storeId)
            ->willReturnSelf();
        $this->storeRepositoryMock
            ->expects($this->once())
            ->method('getWebsiteId')
            ->willReturn($websiteId);

        $stock = $this->getMockForAbstractClass(StockInterface::class);
        $stock->method('getStockId')
            ->willReturn($stockId);
        $this->stockByWebsiteIdResolverMock
            ->method('execute')
            ->willReturn($stock);

        $this->getStockItemDataMock->expects($this->atLeastOnce())
            ->method('execute')
            ->willReturnCallback(
                function ($sku) use ($salability) {
                    return isset($sku)
                        ? ['is_salable' => $salability]
                        : null;
                }
            );

        $this->assertSame(
            $expectedResult,
            $this->plugin->afterMap($this->productDataMapperMock, $documents, [], $storeId)
        );
    }

    /**
     * @return array
     */
    public function stockDataProvider(): array
    {
        return [
            ['storeId' => 1, 'websiteId' => 1, 'stockId' => 1, 'saleability' => 1],
            ['storeId' => 2, 'websiteId' => 20, 'stockId' => 45, 'saleability' => 0],
        ];
    }
}
