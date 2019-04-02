<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\IsProductSalable;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\Framework\App\Config;

class BackorderConditionTest extends TestCase
{
    private const PRODUCT_SKU = 'SKU-2';
    private const NEGATIVE_QTY = -15;
    private const XML_BACKORDERS_CONFIG_VALUE = 'cataloginventory/item_options/backorders';

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StockItemRepositoryInterface
     */
    private $stockItemRepository;

    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    private $stockItemCriteriaFactory;

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
    private $sourceItemsSave;

    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

    /**
     * @var Collection
     */
    private $productCollection;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var InventoryIndexer
     */
    private $inventoryIndexer;

    /**
     * @var Config
     */
    private $appConfig;

    protected function setUp(): void
    {
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->stockItemRepository = Bootstrap::getObjectManager()->get(StockItemRepositoryInterface::class);
        $this->stockItemCriteriaFactory = Bootstrap::getObjectManager()->get(
            StockItemCriteriaInterfaceFactory::class
        );
        $this->sourceItemRepository = Bootstrap::getObjectManager()->get(SourceItemRepositoryInterface::class);
        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
        $this->sourceItemsSave = Bootstrap::getObjectManager()->get(SourceItemsSaveInterface::class);
        $this->isProductSalable = Bootstrap::getObjectManager()->get(IsProductSalableInterface::class);
        $this->productCollection = Bootstrap::getObjectManager()->create(Collection::class);
        $this->configWriter = Bootstrap::getObjectManager()->get(WriterInterface::class);
        $this->inventoryIndexer = Bootstrap::getObjectManager()->get(InventoryIndexer::class);
        $this->appConfig = Bootstrap::getObjectManager()->get(Config::class);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks_with_sales_channel.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @dataProvider backOrdersDataProvider
     *
     * @magentoDbIsolation disabled
     * @param int $backorders
     * @param int $useDefaultBackOrders
     * @param int $exResult
     *
     * @return void
     */
    public function testBackorderedZeroQtyProductIsSalable(
        int $backorders,
        int $useDefaultBackOrders,
        int $exResult
    ): void {
        $product = $this->productRepository->get(self::PRODUCT_SKU);
        $stockItemSearchCriteria = $this->stockItemCriteriaFactory->create();
        $stockItemSearchCriteria->setProductsFilter($product->getId());
        $stockItemsCollection = $this->stockItemRepository->getList($stockItemSearchCriteria);

        /** @var StockItemInterface $legacyStockItem */
        $legacyStockItem = current($stockItemsCollection->getItems());
        $legacyStockItem->setProductId($product->getId());
        $legacyStockItem->setBackorders($backorders);
        $legacyStockItem->setUseConfigBackorders($useDefaultBackOrders);
        $this->stockItemRepository->save($legacyStockItem);

        $sourceItem = $this->getSourceItemBySku(self::PRODUCT_SKU);
        $sourceItem->setQuantity(self::NEGATIVE_QTY);
        $this->sourceItemsSave->execute([$sourceItem]);

        $this->assertEquals($exResult, $this->getProductCount());
    }

    /**
     * @param string $sku
     *
     * @return SourceItemInterface
     */
    private function getSourceItemBySku(string $sku)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('sku', $sku)
            ->create();
        $sourceItemSearchResult = $this->sourceItemRepository->getList($searchCriteria);

        return current($sourceItemSearchResult->getItems());
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks_with_sales_channel.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @dataProvider testUsedDefaultBackorderedZeroQtyProductIsVisibleDataProvider
     *
     * @magentoDbIsolation disabled
     * @param int $globalBackordersValue
     * @param int $useDefaultBackorders
     * @param int $productBackorders
     * @param int $exResult
     *
     * @return void
     */
    public function testUseDefaultBackorderedZeroQtyProductIsVisible(
        int $globalBackordersValue,
        int $useDefaultBackorders,
        int $productBackorders,
        int $exResult
    ): void {
        $product = $this->productRepository->get(self::PRODUCT_SKU);
        $stockItemSearchCriteria = $this->stockItemCriteriaFactory->create();
        $stockItemSearchCriteria->setProductsFilter($product->getId());
        $stockItemsCollection = $this->stockItemRepository->getList($stockItemSearchCriteria);

        /** @var StockItemInterface $legacyStockItem */
        $legacyStockItem = current($stockItemsCollection->getItems());
        $legacyStockItem->setProductId($product->getId());
        $legacyStockItem->setBackorders($productBackorders);
        $legacyStockItem->setUseConfigBackorders($useDefaultBackorders);
        $this->stockItemRepository->save($legacyStockItem);

        $sourceItem = $this->getSourceItemBySku(self::PRODUCT_SKU);
        $sourceItem->setQuantity(self::NEGATIVE_QTY);
        $this->sourceItemsSave->execute([$sourceItem]);

        $this->configWriter->save(
            self::XML_BACKORDERS_CONFIG_VALUE,
            $globalBackordersValue
        );

        $this->appConfig->clean();

        $this->inventoryIndexer->executeFull();

        $this->assertEquals($exResult, $this->getProductCount());
    }

    /**
     * @return array
     */
    public function backOrdersDataProvider(): array
    {
        // [backordersValue for product, use default backorders value, expected result]
        return [
            [0, 0, 0],
            [1, 0, 1],
            [2, 0, 1],
        ];
    }

    /**
     * @return array
     */
    public function testUsedDefaultBackorderedZeroQtyProductIsVisibleDataProvider(): array
    {
        // [default backorders, use default backorders, backorders for product, expected result]
        return [
            [1, 1, 0, 1],
            [2, 1, 0, 1],
            [0, 1, 2, 0],
            [0, 1, 1, 0],
        ];
    }

    /**
     * @return int
     */
    private function getProductCount(): int
    {
        return $this->productCollection
            ->addAttributeToFilter(
                'sku',
                [
                    'eq' => self::PRODUCT_SKU
                ]
            )
            ->count();
    }
}
