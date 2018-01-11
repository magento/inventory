<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\OrderManagement;

use Magento\Sales\Api\OrderManagementInterface;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Indexer\Model\Indexer;
use Magento\Inventory\Indexer\SourceItem\SourceItemIndexer;
use Magento\Inventory\Model\CleanupReservationsInterface;
use Magento\Inventory\Test\Integration\Indexer\RemoveIndexData;
use Magento\InventoryCatalog\Api\DefaultStockProviderInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;

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
     * @var CleanupReservationsInterface
     */
    private $reservationCleanup;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var RemoveIndexData
     */
    private $removeIndexData;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    private $legacyStockItemCriteriaFactory;

    /**
     * @var StockItemRepositoryInterface
     */
    private $legacyStockItemRepository;

    protected function setUp()
    {
        $this->indexer = Bootstrap::getObjectManager()->get(Indexer::class);
        $this->indexer->load(SourceItemIndexer::INDEXER_ID);
        $this->indexer->reindexAll();

        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->orderManagement = Bootstrap::getObjectManager()->get(OrderManagementInterface::class);

        $this->legacyStockItemCriteriaFactory = Bootstrap::getObjectManager()->get(
            StockItemCriteriaInterfaceFactory::class
        );
        $this->legacyStockItemRepository = Bootstrap::getObjectManager()->get(StockItemRepositoryInterface::class);
        $this->reservationCleanup = Bootstrap::getObjectManager()->create(CleanupReservationsInterface::class);

        $this->defaultStockProvider = Bootstrap::getObjectManager()->get(DefaultStockProviderInterface::class);
        $this->removeIndexData = Bootstrap::getObjectManager()->get(RemoveIndexData::class);
    }

    /**
     * We broke transaction during indexation so we need to clean db state manually
     */
    protected function tearDown()
    {
        $this->removeIndexData->execute([$this->defaultStockProvider->getId()]);
        $this->reservationCleanup->execute();
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/source_items_on_default_source.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/order.php
     * @magentoConfigFixture current_store cataloginventory/options/can_subtract 1
     */
    public function testReturnShouldBeAddedToLegacyStockWhenSubtractOptionIsEnabled()
    {
        $productSku = 'SKU-1';
        $product = $this->productRepository->get($productSku);
        $productId = $product->getId();
        $websiteId = 0;
        $orderId = 1;

        /** @var StockItemCriteriaInterface $legacyStockItemCriteria */
        $legacyStockItemCriteria = $this->legacyStockItemCriteriaFactory->create();
        $legacyStockItemCriteria->setProductsFilter($productId);
        $legacyStockItemCriteria->setScopeFilter($websiteId);
        $legacyStockItems = $this->legacyStockItemRepository->getList($legacyStockItemCriteria)->getItems();

        $legacyStockItem = current($legacyStockItems);
        self::assertTrue($legacyStockItem->getIsInStock());
        self::assertEquals(5.5, $legacyStockItem->getQty());

        $this->orderManagement->cancel($orderId);

        $legacyStockItems = $this->legacyStockItemRepository->getList($legacyStockItemCriteria)->getItems();
        $legacyStockItem = current($legacyStockItems);
        self::assertEquals(17.5, $legacyStockItem->getQty());
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/source_items_on_default_source.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/order.php
     * @magentoConfigFixture current_store cataloginventory/options/can_subtract 0
     */
    public function testReturnShouldNotBeAddedToLegacyStockWhenSubtractOptionIsEnabled()
    {
        $productSku = 'SKU-1';
        $product = $this->productRepository->get($productSku);
        $productId = $product->getId();
        $websiteId = 0;
        $orderId = 1;

        /** @var StockItemCriteriaInterface $legacyStockItemCriteria */
        $legacyStockItemCriteria = $this->legacyStockItemCriteriaFactory->create();
        $legacyStockItemCriteria->setProductsFilter($productId);
        $legacyStockItemCriteria->setScopeFilter($websiteId);
        $legacyStockItems = $this->legacyStockItemRepository->getList($legacyStockItemCriteria)->getItems();

        $legacyStockItem = current($legacyStockItems);
        self::assertTrue($legacyStockItem->getIsInStock());
        self::assertEquals(5.5, $legacyStockItem->getQty());

        $this->orderManagement->cancel($orderId);

        $legacyStockItems = $this->legacyStockItemRepository->getList($legacyStockItemCriteria)->getItems();
        $legacyStockItem = current($legacyStockItems);
        self::assertEquals(5.5, $legacyStockItem->getQty());
    }
}
