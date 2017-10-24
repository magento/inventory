<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Inventory\Test\Integration\Stock;

use Magento\Framework\Indexer\IndexerInterface;
use Magento\Inventory\Indexer\StockItemIndexerInterface;
use Magento\InventoryApi\Api\GetProductQuantityInStockInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class GetProductQuantityInStockTest extends TestCase
{
    /**
     * @var IndexerInterface
     */
    private $indexer;

    /**
     * @var GetProductQuantityInStockInterface
     */
    private $getProductQtyInStock;

    protected function setUp()
    {
        $this->indexer = Bootstrap::getObjectManager()->create(IndexerInterface::class);
        $this->indexer->load(StockItemIndexerInterface::INDEXER_ID);
        $this->getProductQtyInStock = Bootstrap::getObjectManager()->create(
            GetProductQuantityInStockInterface::class
        );
    }

    public function tearDown()
    {
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/reservations.php
     */
    public function testGetProductQuantity()
    {
        $this->indexer->reindexRow(1);

        $qty = $this->getProductQtyInStock->execute('SKU-1', 1);
        self::assertEquals(7, $qty);
    }
}
