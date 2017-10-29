<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\InventoryCatalog\Test\Integration\Indexer;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryCatalog\Api\DefaultSourceProviderInterface;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\InventoryApi\Api\Data\SourceItemInterface;


/**
 * TODO: fixture via composer
 */
class SaveInventoryDataObserverTest extends TestCase
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->sourceItemRepository = Bootstrap::getObjectManager()->get(SourceItemRepositoryInterface::class);
        $this->defaultSourceProvider = Bootstrap::getObjectManager()->get(DefaultSourceProviderInterface::class);
        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_link.php
     */
    public function testUpdatingCatalogInventoryQuantity()
    {
        $testData = [
            [
                'sku' => 'SKU-3',
                'quantity' => 56.23,
                'quantity_expected' => 56.2300,
                'status' => 1,
                'status_expected' => SourceItemInterface::STATUS_IN_STOCK,
            ],
            [
                'sku' => 'SKU-3',
                'quantity' => 26,
                'quantity_expected' => 26.0000,
                'status' => 0,
                'status_expected' => SourceItemInterface::STATUS_OUT_OF_STOCK,
            ],
            [
                'sku' => 'SKU-1',
                'quantity' => 55.1234,
                'quantity_expected' => 55.1234,
                'status' => true,
                'status_expected' => SourceItemInterface::STATUS_IN_STOCK,
            ],
        ];

        foreach ($testData as $data) {
            $sku = $data['sku'];
            $quantity = $data['quantity'];
            $quantityExpected = $data['quantity_expected'];
            $status = $data['status'];
            $statusExpected = $data['status_expected'];

            $product = $this->productRepository->get($sku);
            $stockItem = $product->getExtensionAttributes()->getStockItem();
            $stockItem->setQty($quantity);
            $stockItem->setIsInStock($status);
            $this->productRepository->save($product);

            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter(SourceItemInterface::SOURCE_ID, $this->defaultSourceProvider->getId())
                ->addFilter(SourceItemInterface::SKU, $sku)
                ->create();

            $sourceItemResult = $this->sourceItemRepository->getList($searchCriteria);
            $sourceItems = $sourceItemResult->getItems();

            $sourceItem = reset($sourceItems);

            $this->assertEquals(
                $quantityExpected,
                $sourceItem->getQuantity()
            );

            $this->assertEquals(
                $statusExpected,
                (int)$sourceItem->getStatus()
            );
        }
    }
}
