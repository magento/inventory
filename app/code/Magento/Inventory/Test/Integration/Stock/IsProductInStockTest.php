<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Inventory\Test\Integration\Stock;

use Magento\Framework\Indexer\IndexerInterface;
use Magento\Inventory\Indexer\StockItemIndexerInterface;
use Magento\InventoryApi\Api\IsProductInStockInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class IsProductInStockTest extends TestCase
{
    /**
     * @var IndexerInterface
     */
    private $indexer;

    /**
     * @var IsProductInStockInterface
     */
    private $isProductInStock;

    protected function setUp()
    {
        $this->indexer = Bootstrap::getObjectManager()->create(IndexerInterface::class);
        $this->indexer->load(StockItemIndexerInterface::INDEXER_ID);
        $this->isProductInStock = Bootstrap::getObjectManager()->create(IsProductInStockInterface::class);
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
    public function testProductIsInStock()
    {
        $this->indexer->reindexRow(1);

        self::assertTrue($this->isProductInStock->execute('SKU-1', 1));
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/reservations.php
     */
    public function testProductIsNotInStock()
    {
        $this->indexer->reindexRow(1);

        self::assertFalse($this->isProductInStock->execute('SKU-2', 1));
    }
}
