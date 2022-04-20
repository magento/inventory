<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration\Plugin\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product as ResourceProduct;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\MessageQueue\ConsumerFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryCatalog\Plugin\Catalog\Model\ResourceModel\Product\ProcessSourceItemsPlugin;
use Magento\InventoryLowQuantityNotification\Model\ResourceModel\SourceItemConfiguration\GetBySku;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Interception\PluginList;
use Magento\TestFramework\MessageQueue\ClearQueueProcessor;
use PHPUnit\Framework\TestCase;

/**
 * Checks that source items and low stock quantity notification will be removed after product sku has been updated.
 *
 * @see \Magento\InventoryCatalog\Plugin\Catalog\Model\ResourceModel\Product\ProcessSourceItemsPlugin
 * @magentoAppArea adminhtml
 */
class ProcessSourceItemsPluginTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var string */
    private $currentSku;

    /** @var string */
    private $newSku;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->productRepository->cleanCache();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        if ($this->newSku) {
            $this->deleteProductBySku($this->newSku);
        }

        parent::tearDown();
    }

    /**
     * @return void
     */
    public function testProcessSourceItemsPluginIsRegistered(): void
    {
        $pluginInfo = $this->objectManager->get(PluginList::class)->get(ResourceProduct::class);
        $this->assertSame(
            ProcessSourceItemsPlugin::class,
            $pluginInfo['process_source_items_after_product_save']['instance']
        );
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
    public function testUpdateProductSkuSynchronizeWithCatalog(): void
    {
        $this->objectManager->get(ClearQueueProcessor::class)->execute('inventory.source.items.cleanup');
        $this->currentSku = 'SKU-1';
        $this->newSku = 'SKU-1-new';
        $this->updateProductSku($this->currentSku, $this->newSku);
        $this->processMessages();
        self::assertEmpty($this->objectManager->get(GetSourceItemsBySkuInterface::class)->execute($this->currentSku));
        self::assertEmpty($this->objectManager->get(GetBySku::class)->execute($this->currentSku));
    }

    /**
     * Process messages
     *
     * @return void
     */
    private function processMessages(): void
    {
        $consumerFactory = $this->objectManager->get(ConsumerFactory::class);
        $consumer = $consumerFactory->get('inventory.source.items.cleanup');
        $consumer->process(1);
    }

    /**
     * Update product sku
     *
     * @param string $productSku
     * @param string $newSku
     * @return void
     */
    private function updateProductSku(string $productSku, string $newSku): void
    {
        $product = $this->productRepository->get($productSku);
        $product->setSku($newSku);
        $this->productRepository->save($product);
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
}
