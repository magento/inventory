<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryLowQuantityNotification\Test\Api;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\MessageQueue\ConsumerFactory;
use Magento\Framework\MessageQueue\QueueFactoryInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\InventoryLowQuantityNotification\Model\ResourceModel\SourceItemConfiguration\GetBySku;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * Verify, source items configurations will be removed after product has been deleted.
 */
class DeleteProductTest extends WebapiAbstract
{
    private const SERVICE_NAME = 'catalogProductRepositoryV1';
    private const SERVICE_VERSION = 'V1';
    private const RESOURCE_PATH = '/V1/products';

    /**
     * @var GetBySku
     */
    private $getBySku;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->getBySku = Bootstrap::getObjectManager()->get(GetBySku::class);
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->rejectMessages();
    }

    /**
     * Verify, delete product will delete product source items configurations.
     *
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoApiDataFixture ../../../../app/code/Magento/InventoryLowQuantityNotificationApi/Test/_files/source_item_configuration.php
     *
     * @magentoConfigFixture cataloginventory/options/synchronize_with_catalog 1
     */
    public function testDeleteProduct(): void
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . '/SKU-1',
                'httpMethod' => Request::HTTP_METHOD_DELETE,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'DeleteById',
            ],
        ];
        TESTS_WEB_API_ADAPTER === self::ADAPTER_SOAP
            ? $this->_webApiCall($serviceInfo, ['sku' => 'SKU-1'])
            : $this->_webApiCall($serviceInfo);
        $this->runConsumers();
        $sourceItemConfigurations = $this->getBySku->execute('SKU-1');
        self::assertEmpty($sourceItemConfigurations);
    }

    /**
     * Run consumers to remove redundant inventory source items.
     *
     * @return void
     */
    private function runConsumers(): void
    {
        $consumerFactory = Bootstrap::getObjectManager()->get(ConsumerFactory::class);
        $consumer = $consumerFactory->get('inventory.source.items.cleanup');
        $consumer->process(1);
        /*Wait till source items will be removed asynchronously.*/
        sleep(20);
    }

    /**
     * Reject all previously created messages.
     *
     * @return void
     */
    private function rejectMessages()
    {
        $queueFactory = Bootstrap::getObjectManager()->get(QueueFactoryInterface::class);
        $queue = $queueFactory->create('inventory.source.items.cleanup', 'db');
        while ($envelope = $queue->dequeue()) {
            $queue->reject($envelope, false);
        }
    }
}
