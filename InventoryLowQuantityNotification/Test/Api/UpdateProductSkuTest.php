<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryLowQuantityNotification\Test\Api;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Cron;
use Magento\Framework\MessageQueue\ConsumerFactory;
use Magento\Framework\Webapi\Rest\Request;
use Magento\InventoryLowQuantityNotification\Model\ResourceModel\SourceItemConfiguration\GetBySku;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;

/**
 * Verify, source items configurations will be updated after product sku has been updated.
 */
class UpdateProductSkuTest extends WebapiAbstract
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
     * @var ConsumerFactory
     */
    private $consumerFactory;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->getBySku = Bootstrap::getObjectManager()->get(GetBySku::class);
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->consumerFactory = Bootstrap::getObjectManager()->get(ConsumerFactory::class);
    }

    /**
     * Verify, update product sku will update product source items configurations.
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
    public function testUpdateProductSku(): void
    {
        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'Save',
            ],
        ];
        $product = $this->productRepository->get('SKU-1');
        $this->_webApiCall(
            $serviceInfo,
            ['product' => ['id' => $product->getId(), 'sku' => 'SKU-1_updated']]
        );
        $cronObserver = Bootstrap::getObjectManager()->create(
            Cron::class,
            ['parameters' => ['group' => null, 'standaloneProcessStarted' => 0]]
        );
        $cronObserver->launch();
        /*Wait till source items configurations will be removed asynchronously.*/
        sleep(10);
        $sourceItemConfigurationsOldSku = $this->getBySku->execute('SKU-1');
        $sourceItemConfigurationsNewSku = $this->getBySku->execute('SKU-1_updated');
        self::assertEmpty($sourceItemConfigurationsOldSku);
        self::assertNotEmpty($sourceItemConfigurationsNewSku);
    }

    /**
     * Revert product sku. {@inheritDoc}
     */
    protected function tearDown(): void
    {
        $product = $this->productRepository->get('SKU-1_updated');
        $product->setSku('SKU-1');
        $this->productRepository->save($product);
    }
}
