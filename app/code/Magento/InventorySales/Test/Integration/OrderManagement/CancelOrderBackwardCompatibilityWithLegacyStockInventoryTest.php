<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\OrderManagement;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\InventoryApi\Api\GetProductQuantityInStockInterface;
use Magento\Sales\Api\OrderManagementInterface;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Indexer\Model\Indexer;
use Magento\Inventory\Indexer\SourceItem\SourceItemIndexer;

class CancelOrderBackwardCompatibilityWithLegacyStockInventoryTest extends TestCase
{
    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var GetProductQuantityInStockInterface
     */
    private $getProductQtyInStock;

    /**
     * @var StockRegistryInterface
     */
    private $legacyStockItemRegistry;

    protected function setUp()
    {
        $this->indexer = Bootstrap::getObjectManager()->get(Indexer::class);
        $this->indexer->load(SourceItemIndexer::INDEXER_ID);
        $this->indexer->reindexAll();

        $this->orderManagement = Bootstrap::getObjectManager()->get(OrderManagementInterface::class);
        $this->getProductQtyInStock = Bootstrap::getObjectManager()->get(GetProductQuantityInStockInterface::class);
        $this->legacyStockItemRegistry = Bootstrap::getObjectManager()->get(StockRegistryInterface::class);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/source_items_on_default_source.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/order.php
     * @magentoConfigFixture current_store cataloginventory/options/can_subtract 1
     */
    public function testReturnShouldBeAddedToLegacyStockWhenSubtractOptionIsEnabled()
    {
        $orderId = 1;
        $orderItemSku = 'SKU-1';

        $legacyStockItemQty = $this->legacyStockItemRegistry->getStockItemBySku($orderItemSku)->getQty();
        self::assertEquals(5.5, $legacyStockItemQty);

        $this->orderManagement->cancel($orderId);

        $legacyStockItemQty = $this->legacyStockItemRegistry->getStockItemBySku($orderItemSku)->getQty();
        self::assertEquals(17.5, $legacyStockItemQty);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/source_items_on_default_source.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/order.php
     * @magentoConfigFixture current_store cataloginventory/options/can_subtract 0
     */
    public function testReturnShouldNotBeAddedToLegacyStockWhenSubtractOptionIsEnabled()
    {
        $orderId = 1;
        $orderItemSku = 'SKU-1';

        $legacyStockItemQty = $this->legacyStockItemRegistry->getStockItemBySku($orderItemSku)->getQty();
        self::assertEquals(5.5, $legacyStockItemQty);

        $this->orderManagement->cancel($orderId);

        $legacyStockItemQty = $this->legacyStockItemRegistry->getStockItemBySku($orderItemSku)->getQty();
        self::assertEquals(5.5, $legacyStockItemQty);
    }
}
