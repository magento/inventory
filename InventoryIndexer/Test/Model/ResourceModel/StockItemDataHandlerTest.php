<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForSkuInterface;
use Magento\InventoryIndexer\Model\ResourceModel\StockItemDataHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StockItemDataHandlerTest extends TestCase
{
    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceMock;

    /**
     * @var DefaultStockProviderInterface|MockObject
     */
    private $defaultStockProviderMock;

    /**
     * @var GetProductIdsBySkusInterface|MockObject
     */
    private $getProductIdsBySkusMock;

    /**
     * @var IsSingleSourceModeInterface|MockObject
     */
    private $isSingleSourceModeMock;

    /**
     * @var IsSourceItemManagementAllowedForSkuInterface|MockObject
     */
    private $isSourceItemManagementAllowedForSkuMock;

    /**
     * @var StockItemDataHandler
     */
    private StockItemDataHandler $stockItemDataHandler;

    protected function setUp(): void
    {
        $this->resourceMock = $this->createMock(ResourceConnection::class);
        $this->defaultStockProviderMock = $this->createMock(DefaultStockProviderInterface::class);
        $this->getProductIdsBySkusMock = $this->createMock(GetProductIdsBySkusInterface::class);
        $this->isSingleSourceModeMock = $this->createMock(IsSingleSourceModeInterface::class);
        $this->isSourceItemManagementAllowedForSkuMock =
            $this->createMock(IsSourceItemManagementAllowedForSkuInterface::class);

        $this->stockItemDataHandler = new StockItemDataHandler(
            $this->resourceMock,
            $this->defaultStockProviderMock,
            $this->getProductIdsBySkusMock,
            $this->isSingleSourceModeMock,
            $this->isSourceItemManagementAllowedForSkuMock
        );
    }

    public function testGetStockItemDataReturnsNull()
    {
        // Set up the conditions to return null
        $this->defaultStockProviderMock->method('getId')->willReturn(1);
        $this->isSingleSourceModeMock->method('execute')->willReturn(true); // Single source mode is on

        $result = $this->stockItemDataHandler->getStockItemDataFromStockItemTable('sku', 1);
        $this->assertNull($result);
    }

    public function testGetStockItemDataFromDatabase()
    {
        $this->defaultStockProviderMock->method('getId')->willReturn(2);
        $this->isSingleSourceModeMock->method('execute')->willReturn(false);
        $this->isSourceItemManagementAllowedForSkuMock->method('execute')->willReturn(true);
        $this->getProductIdsBySkusMock->method('execute')->willReturn(['productId']);

        $connectionMock = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $this->resourceMock->method('getConnection')->willReturn($connectionMock);

        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $connectionMock->method('select')->willReturn($selectMock);
        $selectMock->method('from')->willReturnSelf();
        $selectMock->method('where')->willReturnSelf();

        $expectedResult = ['qty' => 100, 'is_in_stock' => 1];
        $connectionMock->method('fetchRow')->willReturn($expectedResult);

        $result = $this->stockItemDataHandler->getStockItemDataFromStockItemTable('sku', 2);
        $this->assertEquals($expectedResult, $result);
    }
}
