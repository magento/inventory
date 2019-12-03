<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Integration\Indexer;

use ArrayIterator;
use Magento\InventoryIndexer\Indexer\Stock\PrepareIndexDataForClearingIndex;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test that index is cleared correctly.
 */
class PrepareIndexDataForClearingIndexTest extends TestCase
{
    /**
     * @var PrepareIndexDataForClearingIndex
     */
    private $prepareIndexDataForClearingIndexService;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->prepareIndexDataForClearingIndexService =
            Bootstrap::getObjectManager()->get(PrepareIndexDataForClearingIndex::class);
    }

    /**
     * @magentoDbIsolation disabled
     *
     * @return void
     */
    public function testExecute(): void
    {
        $indexData = new ArrayIterator(
            [
                [
                    'sku' => 'SKU-1',
                    GetStockItemDataInterface::QUANTITY => '10',
                    GetStockItemDataInterface::IS_SALABLE => '0'
                ],
                [
                    'sku' => 'SKU-2',
                    GetStockItemDataInterface::QUANTITY => 10,
                    GetStockItemDataInterface::IS_SALABLE => 0
                ],
                [
                    'sku' => 'SKU-3',
                    GetStockItemDataInterface::QUANTITY => 10,
                    GetStockItemDataInterface::IS_SALABLE => null
                ]
            ]
        );
        $handledIndexData = new ArrayIterator(
            [
                ['sku' => 'SKU-1'],
                ['sku' => 'SKU-2'],
                ['sku' => 'SKU-3']
            ]
        );
        $this->assertEquals(
            $handledIndexData->getArrayCopy(),
            $this->prepareIndexDataForClearingIndexService->execute($indexData)->getArrayCopy()
        );
    }
}
