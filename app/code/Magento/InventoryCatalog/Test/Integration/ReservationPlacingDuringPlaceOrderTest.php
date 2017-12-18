<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\InventoryApi\Api\GetProductQuantityInStockInterface;
use Magento\Indexer\Model\Indexer;
use Magento\Inventory\Indexer\SourceItem\SourceItemIndexer;
use Magento\Store\Model\Website;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;

class ReservationPlacingDuringPlaceOrderTest extends TestCase
{
    /**
     * @var GetProductQuantityInStockInterface
     */
    private $getProductQtyInStock;

    /**
     * @var Indexer
     */
    private $indexer;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Inventory\Model\StockRepository
     */
    private $stockRepository;

    /**
     * @var \Magento\CatalogInventory\Model\StockManagement
     */
    private $stockManagement;

    /**
     * @var \Magento\Store\Model\WebsiteRepository
     */
    private $websiteRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->indexer = Bootstrap::getObjectManager()->get(Indexer::class);
        $this->indexer->load(SourceItemIndexer::INDEXER_ID);
        $this->getProductQtyInStock = Bootstrap::getObjectManager()->get(GetProductQuantityInStockInterface::class);
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->stockRepository = Bootstrap::getObjectManager()->get(\Magento\Inventory\Model\StockRepository::class);
        $this->stockManagement = Bootstrap::getObjectManager()->get(\Magento\CatalogInventory\Model\StockManagement::class);
        $this->websiteRepository = Bootstrap::getObjectManager()->get(\Magento\Store\Model\WebsiteRepository::class);

        $extensionAttributes = Bootstrap::getObjectManager()->create(\Magento\InventoryApi\Api\Data\StockExtension::class);
        $salesChannel = Bootstrap::getObjectManager()->create(\Magento\InventorySales\Model\SalesChannel::class);
        $salesChannel->setData([
            SalesChannelInterface::TYPE => SalesChannelInterface::TYPE_WEBSITE,
            SalesChannelInterface::CODE => 'test_1',
        ]);
        $extensionAttributes->setSalesChannels([$salesChannel]);

        $stock = $this->stockRepository->get(10);
        $stock->setExtensionAttributes($extensionAttributes);

        $this->stockRepository->save($stock);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_link.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites.php
     */
    public function testRegisterProductsSale()
    {
        $sellQuantity = 3.5;

        $this->indexer->reindexAll();
        $this->assertEquals(8.5, $this->getProductQtyInStock->execute('SKU-1', 10));

        $product = $this->productRepository->get('SKU-1');
        $website = $this->websiteRepository->get('test_1');

        $itemsToRegister = [
            $product->getId() => $sellQuantity
        ];

        $this->stockManagement->registerProductsSale($itemsToRegister, $website->getId());

        $this->assertEquals(8.5 - $sellQuantity, $this->getProductQtyInStock->execute('SKU-1', 10));
    }
}
