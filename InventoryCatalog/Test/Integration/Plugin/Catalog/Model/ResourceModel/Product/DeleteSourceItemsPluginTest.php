<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration\Plugin\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\MessageQueue\ConsumerFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Test\Fixture\Source as SourceFixture;
use Magento\InventoryCatalogApi\Api\BulkSourceAssignInterface;
use Magento\InventoryCatalogApi\Api\BulkSourceUnassignInterface;
use Magento\InventoryImportExport\Test\Integration\Model\ProductImportExportBase;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\MessageQueue\ClearQueueProcessor;

class DeleteSourceItemsPluginTest extends ProductImportExportBase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /** @var ProductRepositoryInterface */
    private $productRepository;

    /**
     * @var BulkSourceUnassignInterface
     */
    private $bulkSourceUnassign;

    /**
     * @var BulkSourceAssignInterface
     */
    private $bulkSourceAssign;

    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->bulkSourceUnassign = $this->objectManager->get(BulkSourceUnassignInterface::class);
        $this->bulkSourceAssign = $this->objectManager->get(BulkSourceAssignInterface::class);
        $this->getSourceItemsBySku = $this->objectManager->get(GetSourceItemsBySkuInterface::class);
        $this->fixtures = DataFixtureStorageManager::getStorage();
    }

    /**
     * @magentoAppArea adminhtml
     * @throws LocalizedException
     */
    #[
        DbIsolation(true),
        DataFixture(ProductFixture::class, ['sku' => 'SKU-1'], 'p1'),
        DataFixture(SourceFixture::class, ['source_code' => 'source2'], as: 'src1'),
    ]
    public function testDeleteSourceItemsPlugin(): void
    {
        $defaultSourceCode = 'default';
        $sku = $this->fixtures->get('p1')->getSku();
        $customSourceCode = $this->fixtures->get('src1')->getSourceCode();
        $this->objectManager->get(ClearQueueProcessor::class)->execute('inventory.source.items.cleanup');

        // export csv for products data
        $productExporter = $this->getProductExporter();
        $productExporter->export();

        // Unassigned default source to product and assign it `custom` source
        $this->bulkSourceUnassign->execute([$sku], [$defaultSourceCode]);
        $this->bulkSourceAssign->execute([$sku], [$customSourceCode]);

        //delete product & execute cron
        $this->deleteProducts();
        $this->processMessages();

        //import previously exported csv
        $productImporter = $this->getProductImporter();
        $errors = $productImporter->validateData();
        $this->assertTrue($errors->getErrorsCount() === 0);
        $productImporter->importData();

        $importedProducts = $this->getImportedProducts();
        $this->assertCount(1, $importedProducts);
        $importedProduct = current($importedProducts);
        $product = $this->productRepository->get($sku);
        $this->assertEquals($product->getSku(), $importedProduct->getSku());
        $this->assertEquals($product->getTypeId(), $importedProduct->getTypeId());
        $this->assertEquals($product->getName(), $importedProduct->getName());
        $this->assertEquals($product->getPrice(), $importedProduct->getPrice());

        $sourceItems = $this->getSourceItemsBySku->execute($sku);
        $this->assertCount(1, $sourceItems);
        $sourceItem = current($sourceItems);
        $this->assertEquals([$sourceItem->getSourceCode()], [$defaultSourceCode]);
    }

    /**
     * Process messages queue
     *
     * @return void
     */
    private function processMessages(): void
    {
        $consumerFactory = $this->objectManager->get(ConsumerFactory::class);
        $consumer = $consumerFactory->get('inventory.source.items.cleanup');
        $consumer->process(1);
    }
}
