<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryProductAlert\Test\Integration;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\MysqlMq\Model\QueueManagement;
use Magento\ProductAlert\Model\Observer;
use Magento\ProductAlert\Model\ResourceModel\Stock\CollectionFactory as StockCollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\MessageQueue\EnvironmentPreconditionException;
use Magento\TestFramework\MessageQueue\PreconditionFailedException;
use Magento\TestFramework\MessageQueue\PublisherConsumerController;
use PHPUnit\Framework\TestCase;

/**
 * Check product alerts work on multi source.
 */
class ProductAlertTest extends TestCase
{
    /**
     * @var Observer
     */
    private $observer;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSaveInterface;

    /**
     * @var StockCollectionFactory
     */
    private $stockCollectionFactory;

    /**
     * @var PublisherConsumerController
     */
    private $publisherConsumerController;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->observer = Bootstrap::getObjectManager()->create(Observer::class);
        $this->sourceItemRepository = Bootstrap::getObjectManager()->get(SourceItemRepositoryInterface::class);
        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
        $this->sourceItemsSaveInterface = Bootstrap::getObjectManager()->get(SourceItemsSaveInterface::class);
        $this->stockCollectionFactory = Bootstrap::getObjectManager()->get(StockCollectionFactory::class);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryCatalog::Test/_files/source_items_on_default_source.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento_InventoryProductAlert::Test/_files/product_alert_customer.php
     * @magentoDataFixture Magento_InventoryProductAlert::Test/_files/customer_eu_website_id.php
     * @magentoDataFixture Magento_InventoryProductAlert::Test/_files/product_alert_eu_website_customer.php
     * @magentoConfigFixture default_store catalog/productalert/allow_stock 1
     * @magentoConfigFixture store_for_eu_website_store catalog/productalert/allow_stock 1
     *
     * @magentoDbIsolation disabled
     */
    public function testAlertsBothSourceItemsOutOfStock()
    {
        $maxId = $this->getMaxBulkOperationId();
        $this->observer->process();
        $bulkUUIDs = $this->getBulkOperationUUIDsAfterId($maxId);
        $this->assertEquals(2, count($bulkUUIDs), 'There is more(less) than two bulk operation created!');

        $this->waitingForProcessAlertsByConsumer(2, $bulkUUIDs);

        $stockCollection = $this->stockCollectionFactory->create();
        $count = 0;
        /** @var \Magento\ProductAlert\Model\Stock $stock */
        foreach ($stockCollection as $stock) {
            $count += $stock->getSendCount();
        }
        $this->assertEquals(0, $count);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryCatalog::Test/_files/source_items_on_default_source.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento_InventoryProductAlert::Test/_files/product_alert_customer.php
     * @magentoDataFixture Magento_InventoryProductAlert::Test/_files/customer_eu_website_id.php
     * @magentoDataFixture Magento_InventoryProductAlert::Test/_files/product_alert_eu_website_customer.php
     * @magentoConfigFixture default_store catalog/productalert/allow_stock 1
     * @magentoConfigFixture store_for_eu_website_store catalog/productalert/allow_stock 1
     *
     * @magentoAppArea frontend
     * @magentoDbIsolation disabled
     */
    public function testAlertsOneSourceItemInStock()
    {
        $maxId = $this->getMaxBulkOperationId();
        $this->observer->process();
        $stockCollection = $this->stockCollectionFactory->create();
        $count = 0;
        /** @var \Magento\ProductAlert\Model\Stock $stock */
        foreach ($stockCollection as $stock) {
            $count += $stock->getSendCount();
        }
        $this->assertEquals(0, $count);

        $this->changeProductIsInStock('eu-2', 1);
        $this->observer->process();

        $bulkUUIDs = $this->getBulkOperationUUIDsAfterId($maxId);
        $this->assertEquals(4, count($bulkUUIDs), 'There is more(less) than two bulk operation created!');
        $this->waitingForProcessAlertsByConsumer(4, $bulkUUIDs);

        $stockCollection = $this->stockCollectionFactory->create();
        $count = 0;
        /** @var \Magento\ProductAlert\Model\Stock $stock */
        foreach ($stockCollection as $stock) {
            $count += $stock->getSendCount();
        }
        $this->assertEquals(1, $count);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryCatalog::Test/_files/source_items_on_default_source.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento_InventoryProductAlert::Test/_files/product_alert_customer.php
     * @magentoDataFixture Magento_InventoryProductAlert::Test/_files/customer_eu_website_id.php
     * @magentoDataFixture Magento_InventoryProductAlert::Test/_files/product_alert_eu_website_customer.php
     * @magentoConfigFixture default_store catalog/productalert/allow_stock 1
     * @magentoConfigFixture store_for_eu_website_store catalog/productalert/allow_stock 1
     *
     * @magentoAppArea frontend
     * @magentoDbIsolation disabled
     */
    public function testAlertsBothSourceItemsInStock()
    {
        $this->changeProductIsInStock('eu-2', 1);
        $this->changeProductIsInStock('default', 1);

        $maxId = $this->getMaxBulkOperationId();
        $this->observer->process();

        $bulkUUIDs = $this->getBulkOperationUUIDsAfterId($maxId);
        $this->assertEquals(2, count($bulkUUIDs), 'There is more(less) than two bulk operation created!');

        $this->waitingForProcessAlertsByConsumer(2, $bulkUUIDs);

        $magentoOperations = $this->getMagentoOperationsByBulkUUIDs($bulkUUIDs);
        $this->assertEquals(2, count($magentoOperations), 'There is more(less) than two bulk operation processed!');

        foreach ($magentoOperations as $row) {
            $this->assertEquals(
                'Product alerts are sent successfully.',
                $row['result_message'],
                'Product alert didnt sent, error: ' . $row['result_message']
            );
        }

        $stockCollection = $this->stockCollectionFactory->create();
        $count = 0;
        /** @var \Magento\ProductAlert\Model\Stock $stock */
        foreach ($stockCollection as $stock) {
            $count += $stock->getSendCount();
        }
        $this->assertEquals(2, $count);
    }

    /**
     * @param string $sourceCode
     * @param int $isInStock
     *
     * @return void
     */
    private function changeProductIsInStock(string $sourceCode, int $isInStock)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, 'SKU-3')
            ->addFilter(SourceItemInterface::SOURCE_CODE, $sourceCode)
            ->create();

        $items = $this->sourceItemRepository->getList($searchCriteria)->getItems();
        /** @var SourceItemInterface $sourceItem */
        $sourceItem = reset($items);
        $sourceItem->setStatus($isInStock);
        if ($isInStock) {
            $sourceItem->setQuantity($sourceItem->getQuantity() ?: 1.0);
        }
        $this->sourceItemsSaveInterface->execute([$sourceItem]);
    }

    /**
     * Run consumer
     *
     * @param int $maxMessageCount
     */
    private function startConsumer(int $maxMessageCount): void
    {
        $this->publisherConsumerController = Bootstrap::getObjectManager()->create(
            PublisherConsumerController::class,
            [
                'consumers' => ['product_alert'],
                'logFilePath' => TESTS_TEMP_DIR . "/MessageQueueTestLog.txt",
                'maxMessages' => $maxMessageCount,
                'appInitParams' => Bootstrap::getInstance()->getAppInitParams()
            ]
        );
        try {
            $this->publisherConsumerController->startConsumers();
        } catch (EnvironmentPreconditionException $e) {
            $this->markTestSkipped($e->getMessage());
        } catch (PreconditionFailedException $e) {
            $this->fail(
                $e->getMessage()
            );
        }
    }

    /**
     * Waiting for execute consumer
     *
     * @param int $maxMessageCount
     * @param array $bulkUUIDs
     * @return void
     * @throws PreconditionFailedException
     */
    private function waitingForProcessAlertsByConsumer(int $maxMessageCount, array $bulkUUIDs): void
    {
        $this->startConsumer($maxMessageCount);

        sleep(30); // timeout to processing Magento queue

        $this->publisherConsumerController->waitForAsynchronousResult(
            function ($bulkUUIDs) {
                return $this->isProcessedStockAlerts($bulkUUIDs);
            },
            [$bulkUUIDs]
        );
    }

    /**
     * Is has been already processed stock alerts
     *
     * @param array $bulkUUIDs
     * @return bool
     */
    private function isProcessedStockAlerts(array $bulkUUIDs): bool
    {
        $collection = $this->stockCollectionFactory->create();
        $connection = $collection->getConnection();
        $select = $connection->select();
        $select->from(
            ['t' => $connection->getTableName('magento_operation')],
            [new \Zend_Db_Expr('COUNT(*)')]
        )->where(
            't.bulk_uuid IN (?)',
            $bulkUUIDs
        );

        return (int)$connection->fetchOne($select) === count($bulkUUIDs);
    }

    /**
     * Get current max ID of magento_bulk
     * @return int
     */
    private function getMaxBulkOperationId(): int
    {
        $collection = $this->stockCollectionFactory->create();
        $connection = $collection->getConnection();
        $select = $connection->select();
        $select->from(
            ['t' => $connection->getTableName('magento_bulk')],
            [new \Zend_Db_Expr('MAX(t.id)')]
        );

        return (int)$connection->fetchOne($select);
    }

    /**
     * Get list of UUIDs from rows where id > maxId
     *
     * @param int $maxId
     * @return array
     */
    private function getBulkOperationUUIDsAfterId(int $maxId): array
    {
        $collection = $this->stockCollectionFactory->create();
        $connection = $collection->getConnection();
        $select = $connection->select();
        $select->from(
            ['t' => $connection->getTableName('magento_bulk')],
            ['uuid']
        )->where('t.id > ?', $maxId);

        return $connection->fetchCol($select);
    }

    /**
     * Get list of Magento Operations by its UUIDs
     *
     * @param array $bulkUUIDs
     * @return array
     */
    private function getMagentoOperationsByBulkUUIDs(array $bulkUUIDs): array
    {
        $collection = $this->stockCollectionFactory->create();
        $connection = $collection->getConnection();
        $select = $connection->select();
        $select->from(
            ['t' => $connection->getTableName('magento_operation')]
        )->where(
            't.bulk_uuid IN (?)',
            $bulkUUIDs
        );
        return $connection->fetchAll($select);
    }
}
