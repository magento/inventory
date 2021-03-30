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
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\MessageQueue\QueueFactoryInterface;
use Magento\Framework\MessageQueue\QueueInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryCatalog\Model\DeleteSourceItemsBySkus;
use Magento\InventoryCatalog\Plugin\Catalog\Model\ResourceModel\Product\ProcessSourceItemsPlugin;
use Magento\InventoryLowQuantityNotification\Model\ResourceModel\SourceItemConfiguration\GetBySku;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Interception\PluginList;
use Magento\TestFramework\MysqlMq\DeleteTopicRelatedMessages;
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

    /** @var DeleteTopicRelatedMessages */
    private $deleteTopicMessages;

    /** @var QueueInterface */
    private $queue;

    /** @var MessageEncoder */
    private $messageEncoder;

    /** @var DeleteSourceItemsBySkus */
    private $handler;

    /** @var string */
    private $currentSku;

    /** @var string */
    private $newSku;

    /** @var GetSourceItemsBySkuInterface */
    private $getSourceItemsBySku;

    /** @var GetBySku */
    private $getSourceItemConfigurationsBySku;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->deleteTopicMessages = $this->objectManager->get(DeleteTopicRelatedMessages::class);
        $this->queue = $this->objectManager->get(QueueFactoryInterface::class)->create(
            'inventory.source.items.cleanup',
            'db'
        );
        $this->messageEncoder = $this->objectManager->get(MessageEncoder::class);
        $this->handler = $this->objectManager->get(DeleteSourceItemsBySkus::class);
        $this->getSourceItemsBySku = $this->objectManager->get(GetSourceItemsBySkuInterface::class);
        $this->getSourceItemConfigurationsBySku = $this->objectManager->get(GetBySku::class);
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
        $this->deleteTopicMessages->execute('inventory.source.items.cleanup');
        $this->currentSku = 'SKU-1';
        $this->newSku = 'SKU-1-new';
        $this->updateProductSku($this->currentSku, $this->newSku);
        $this->processMessages('inventory.source.items.cleanup');
        self::assertEmpty($this->getSourceItemsBySku->execute($this->currentSku));
        self::assertEmpty($this->getSourceItemConfigurationsBySku->execute($this->currentSku));
    }

    /**
     * Process topic messages
     *
     * @param string $topicName
     * @return void
     */
    private function processMessages(string $topicName): void
    {
        $envelope = $this->queue->dequeue();
        $decodedMessage = $this->messageEncoder->decode($topicName, $envelope->getBody());
        $this->handler->execute($decodedMessage);
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
