<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Model;

use Magento\InventoryIndexer\Model\AreMultipleProductsSalable;
use Magento\InventorySalesApi\Model\GetStockItemsDataInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class AreMultipleProductsSalableTest extends TestCase
{
    /**
     * @var GetStockItemsDataInterface|MockObject
     */
    private $getStockItemsDataMock;

    /**
     * @var LoggerInterface|MockObject
     */
    private $loggerMock;

    /**
     * @var AreMultipleProductsSalable
     */
    private AreMultipleProductsSalable $areMultipleProductsSalable;

    protected function setUp(): void
    {
        $this->getStockItemsDataMock = $this->createMock(GetStockItemsDataInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->areMultipleProductsSalable = new AreMultipleProductsSalable(
            $this->getStockItemsDataMock,
            $this->loggerMock
        );
    }

    public function testExecuteWithSuccessfulDataFetch()
    {
        $skus = ['sku1', 'sku2'];
        $stockId = 123;
        $stockItemsData = [
            'sku1' => [GetStockItemsDataInterface::IS_SALABLE => true],
            'sku2' => [GetStockItemsDataInterface::IS_SALABLE => false],
        ];

        $this->getStockItemsDataMock
            ->method('execute')
            ->with($skus, $stockId)
            ->willReturn($stockItemsData);

        $result = $this->areMultipleProductsSalable->execute($skus, $stockId);

        $this->assertEquals([
            'sku1' => true,
            'sku2' => false,
        ], $result);
    }

    public function testExecuteWithException()
    {
        $skus = ['sku1', 'sku2'];
        $stockId = 123;
        $exceptionMessage = 'Error fetching stock data';

        $this->getStockItemsDataMock
            ->method('execute')
            ->with($skus, $stockId)
            ->willThrowException(new LocalizedException(__($exceptionMessage)));

        $this->loggerMock
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains($exceptionMessage));

        $result = $this->areMultipleProductsSalable->execute($skus, $stockId);

        $this->assertEquals([
            'sku1' => false,
            'sku2' => false,
        ], $result);
    }
}
