<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryLowQuantityNotificationAdminUi\Test\Integration\Observer;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Save;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryLowQuantityNotification\Model\ResourceModel\SourceItemConfiguration\DeleteMultiple;
use Magento\InventoryLowQuantityNotification\Model\ResourceModel\SourceItemConfiguration\GetBySku;
use Magento\InventoryLowQuantityNotificationAdminUi\Observer\ProcessSourceItemConfigurationsObserver;
use Magento\InventoryLowQuantityNotificationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Checks that the entries in the inventory_low_stock_notification_configuration table
 * have been updated correctly for the current product
 *
 * @see \Magento\InventoryLowQuantityNotificationAdminUi\Observer\ProcessSourceItemConfigurationsObserver
 * @magentoAppArea adminhtml
 */
class ProcessSourceItemConfigurationsObserverTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var ProcessSourceItemConfigurationsObserver */
    private $observer;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /** @var Save */
    private $adminProductSaveController;

    /** @var string */
    private $currentSku;

    /** @var string */
    private $newSku;

    /** @var GetBySku */
    private $getSourceItemConfigurationsBySku;

    /** @var DeleteMultiple */
    private $deleteSourceItemConfigurations;

    /** @var DataObjectFactory */
    private $dataObjectFactory;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->observer = $this->objectManager->get(ProcessSourceItemConfigurationsObserver::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->productRepository->cleanCache();
        $this->adminProductSaveController = $this->objectManager->get(Save::class);
        $this->getSourceItemConfigurationsBySku = $this->objectManager->get(GetBySku::class);
        $this->deleteSourceItemConfigurations = $this->objectManager->get(DeleteMultiple::class);
        $this->dataObjectFactory = $this->objectManager->get(DataObjectFactory::class);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        if ($this->newSku) {
            $this->deleteProductBySku($this->newSku);
            $this->clearLowStockNotificationBySku($this->newSku);
        }

        parent::tearDown();
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryLowQuantityNotificationApi::Test/_files/source_item_configuration.php
     * @magentoConfigFixture default/cataloginventory/options/synchronize_with_catalog 1
     * @magentoDbIsolation disabled
     * @return void
     */
    public function testUpdateProductSkuInMultipleSourceMode(): void
    {
        $this->currentSku = 'SKU-1';
        $this->newSku = 'SKU-1-new';
        $product = $this->productRepository->get($this->currentSku);
        $assignedSources = $this->prepareAssignedSources($product->getSku());

        $product = $this->updateProductSku($this->currentSku, $this->newSku);
        $this->prepareAdminProductSaveController($product, $assignedSources);
        $this->observer->execute($this->getEventObserver($product));

        $this->assertCount(
            count($assignedSources),
            $this->getSourceItemConfigurationsBySku->execute($this->currentSku),
            sprintf(
                'The expected quantity of low stock notification for the current sku: %s is not correct.',
                $this->currentSku
            )
        );
        $this->assertCount(
            count($assignedSources),
            $this->getSourceItemConfigurationsBySku->execute($this->newSku),
            sprintf(
                'The expected quantity of low stock notification for the new sku: %s is not correct.',
                $this->newSku
            )
        );
    }

    /**
     * Initialize observer event's for tests.
     *
     * @param ProductInterface $product
     * @return Observer
     */
    private function getEventObserver(ProductInterface $product): Observer
    {
        $event = $this->dataObjectFactory->create();
        $event->setController($this->adminProductSaveController)
            ->setProduct($product);

        /** @var Observer $eventObserver */
        $eventObserver = $this->objectManager->create(Observer::class);
        $eventObserver->setEvent($event);

        return $eventObserver;
    }

    /**
     * Update product sku
     *
     * @param string $productSku
     * @param string $newSku
     * @return ProductInterface
     */
    private function updateProductSku(string $productSku, string $newSku): ProductInterface
    {
        $product = $this->productRepository->get($productSku);
        $product->setSku($newSku);

        return $this->productRepository->save($product);
    }

    /**
     * Prepare admin product save controller
     *
     * @param ProductInterface $product
     * @param array $assignedSources
     * @return void
     */
    private function prepareAdminProductSaveController(ProductInterface $product, array $assignedSources): void
    {
        $this->adminProductSaveController->getRequest()->setParams([
            'product' => $product->getData(),
            'sources' => [
                'assigned_sources' => $assignedSources,
            ],
        ]);
    }

    /**
     * Delete product by sku in secure area
     *
     * @param string $sku
     * @return void
     */
    private function deleteProductBySku(string $sku): void
    {
        try {
            $product = $this->productRepository->get($sku);
            $this->productRepository->delete($product);
        } catch (NoSuchEntityException $exception) {
            // product doesn't exist;
        }
    }

    /**
     * Prepare assigned sources
     *
     * @param string $sku
     * @return array
     */
    private function prepareAssignedSources(string $sku): array
    {
        $sourceItemsConfigurations = $this->getSourceItemConfigurationsBySku->execute($sku);
        $assignedSourceCodes = [];
        foreach ($sourceItemsConfigurations as $sourceNotifyQty) {
            $assignedSourceCodes[] = [
                SourceItemConfigurationInterface::SKU => $sourceNotifyQty->getSku(),
                SourceItemConfigurationInterface::SOURCE_CODE => $sourceNotifyQty->getSourceCode(),
                SourceItemConfigurationInterface::INVENTORY_NOTIFY_QTY => $sourceNotifyQty->getNotifyStockQty(),
                'notify_stock_qty_use_default' => is_null($sourceNotifyQty->getNotifyStockQty()) ? '1' : '0',
            ];
        }

        return $assignedSourceCodes;
    }

    /**
     * Delete items by sku from inventory_low_stock_notification_configuration table
     *
     * @param string $sku
     * @return void
     */
    private function clearLowStockNotificationBySku(string $sku): void
    {
        $sourceItemsConfigurations = $this->getSourceItemConfigurationsBySku->execute($sku);
        $this->deleteSourceItemConfigurations->execute($sourceItemsConfigurations);
    }
}
