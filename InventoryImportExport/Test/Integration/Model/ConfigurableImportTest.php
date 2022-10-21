<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Test\Integration\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\CatalogImportExport\Model\Import\Product;
use Magento\CatalogImportExport\Model\Import\ProductFactory;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\MessageQueue\MessageEncoder;
use Magento\Framework\MessageQueue\QueueFactoryInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Source\Csv;
use Magento\ImportExport\Model\Import\Source\CsvFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Verify configurable product legacy stock status after import
 *
 * @magentoAppArea adminhtml
 */
class ConfigurableImportTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ProductFactory
     */
    private $productImporterFactory;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->filesystem = $this->objectManager->get(Filesystem::class);
        $this->productImporterFactory = $this->objectManager->get(ProductFactory::class);
    }

    /**
     * Verify configurable product import from export on additional stock.
     *
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento/ConfigurableProduct/_files/configurable_attribute.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/product_configurable.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/source_items_configurable.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/source_items_configurable_adjustment_for_default.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     *
     * @magentoDbIsolation disabled
     *
     * @return void
     */
    public function testImportConfigurableProduct(): void
    {
        $pathToFile = __DIR__ . '/Import/_files/configurable_product_import.csv';
        $behavior = Import::BEHAVIOR_ADD_UPDATE;
        $directory = $this->filesystem
            ->getDirectoryWrite(DirectoryList::ROOT);
        /** @var Csv $source */
        $source = $this->objectManager->get(CsvFactory::class)->create(
            [
                'file' => $pathToFile,
                'directory' => $directory
            ]
        );
        $productImporter = $this->productImporterFactory->create();
        $productImporter->setParameters(
            [
                'behavior' => $behavior,
                'entity' => ProductModel::ENTITY
            ]
        )->setSource($source);
        $errors = $productImporter->validateData();
        $this->assertTrue($errors->getErrorsCount() == 0);
        $productImporter->importData();

        $sku = 'Configurable';
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        /** @var ProductInterface $product */
        $product = $productRepository->get($sku, true, null, true);

        $criteriaFactory = $this->objectManager->create(StockItemCriteriaInterfaceFactory::class);
        $stockItemRepository = $this->objectManager->create(StockItemRepositoryInterface::class);
        $stockConfiguration = $this->objectManager->create(StockConfigurationInterface::class);
        $criteria = $criteriaFactory->create();
        $criteria->setScopeFilter($stockConfiguration->getDefaultScopeId());
        $criteria->setProductsFilter((int) $product->getId());
        $stockItemCollection = $stockItemRepository->getList($criteria);
        $stockItems = $stockItemCollection->getItems();
        $stockItem = reset($stockItems);

        $this->assertNotNull($stockItem);
        $this->assertTrue($stockItem->getIsInStock());
    }
}
