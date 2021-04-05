<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\MessageQueue\QueueFactoryInterface;
use Magento\Framework\MessageQueue\QueueInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\MysqlMq\DeleteTopicRelatedMessages;
use PHPUnit\Framework\TestCase;
use Magento\InventoryCatalog\Model\DeleteSourceItemsBySkus;
use Magento\InventoryLowQuantityNotification\Model\ResourceModel\SourceItemConfiguration\GetBySku;

/**
 * Test for delete product source
 *
 * @magentoAppArea adminhtml
 */
class DeleteProductTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var QueueInterface
     */
    private $queue;

    /**
     * @var MessageEncoder
     */
    private $messageEncoder;

    /**
     * @var DeleteSourceItemsBySkus
     */
    private $handler;

    /**
     * @var DeleteTopicRelatedMessages
     */
    private $deleteTopicMessages;

    /**
     * @var GetBySku
     */
    private $getBySku;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->queue = $this->objectManager->get(QueueFactoryInterface::class)->create(
            'inventory.source.items.cleanup',
            'db'
        );
        $this->messageEncoder = $this->objectManager->get(MessageEncoder::class);
        $this->handler = $this->objectManager->get(DeleteSourceItemsBySkus::class);
        $this->deleteTopicMessages = $this->objectManager->get(DeleteTopicRelatedMessages::class);
        $this->getBySku = $this->objectManager->get(GetBySku::class);
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->productRepository->cleanCache();
        $this->getSourceItemsBySku = $this->objectManager->get(GetSourceItemsBySkuInterface::class);
    }

    /**
     * @magentoConfigFixture default/cataloginventory/options/synchronize_with_catalog 1
     *
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryLowQuantityNotificationApi::Test/_files/source_item_configuration.php
     *
     * @return void
     */
    public function testSourceItemDeletedOnProductImport(): void
    {
        $this->deleteTopicMessages->execute('inventory.source.items.cleanup');
        $productSku = 'SKU-1';
        $this->productRepository->deleteById($productSku);
        $this->processMessages('inventory.source.items.cleanup');

        $sourceItems = $this->getSourceItemsBySku->execute($productSku);
        self::assertEmpty($sourceItems);

        $sourceItemConfigurations = $this->getBySku->execute('SKU-1');
        self::assertEmpty($sourceItemConfigurations);
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
}
