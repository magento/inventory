<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration\Plugin\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\MessageQueue\QueueFactoryInterface;
use Magento\Framework\MessageQueue\QueueInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryCatalog\Model\DeleteSourceItemsBySkus;
use Magento\InventoryLowQuantityNotification\Model\ResourceModel\SourceItemConfiguration\GetBySku;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\MysqlMq\DeleteTopicRelatedMessages;
use PHPUnit\Framework\TestCase;

/**
 * Checks that source items and low stock quantity notification will be removed after product sku has been updated.
 *
 * @see \Magento\InventoryCatalog\Plugin\Catalog\Model\ResourceModel\Product\ProcessSourceItemsPlugin
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
    private $consumer;

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
        $this->objectManager = Bootstrap::getObjectManager();
        $this->deleteTopicMessages = $this->objectManager->get(DeleteTopicRelatedMessages::class);
        $this->queue = $this->objectManager->get(QueueFactoryInterface::class)->create(
            'inventory.source.items.cleanup',
            'db'
        );
        $this->messageEncoder = $this->objectManager->get(MessageEncoder::class);
        $this->consumer = $this->objectManager->get(DeleteSourceItemsBySkus::class);
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
        if ($this->getName() == 'testUpdateProductSkuSynchronizeWithCatalog') {
            $this->clearNewSku($this->newSku, $this->currentSku);
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
     * @magentoConfigFixture cataloginventory/options/synchronize_with_catalog 1
     * @magentoDbIsolation disabled
     * @return void
     */
    public function testUpdateProductSkuSynchronizeWithCatalog(): void
    {
        $this->deleteTopicMessages->execute('inventory.source.items.cleanup');
        $this->currentSku = 'SKU-1';
        $this->newSku = 'SKU-1' . '-new';
        $this->updateProduct($this->currentSku, ['sku' => $this->newSku]);
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
        $this->consumer->execute($decodedMessage);
    }

    /**
     * Update product
     *
     * @param string $productSku
     * @param array $data
     * @return ProductInterface
     */
    private function updateProduct(string $productSku, array $data): ProductInterface
    {
        $product = $this->productRepository->get($productSku);
        $product->addData($data);

        return $this->productRepository->save($product);
    }

    /**
     * Returns the old product sku
     *
     * @param string $newSku
     * @param string $previousSku
     * @return void
     */
    private function clearNewSku(string $newSku, string $previousSku): void
    {
        try {
            $product = $this->productRepository->get($newSku);
            $product->setSku($previousSku);
            $this->productRepository->save($product);
        } catch (NoSuchEntityException $exception) {
            // product doesn't exist;
        }
    }
}
