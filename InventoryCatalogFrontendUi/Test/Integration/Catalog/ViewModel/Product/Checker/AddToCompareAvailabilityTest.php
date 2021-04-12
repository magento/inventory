<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogFrontendUi\Test\Integration\Catalog\ViewModel\Product\Checker;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\ViewModel\Product\Checker\AddToCompareAvailability;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test add to compare availability in multi stock environment.
 */
class AddToCompareAvailabilityTest extends TestCase
{
    /**
     * @var AddToCompareAvailability
     */
    private $addToCompareAvailability;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @inheridoc
     */
    protected function setUp(): void
    {
        $this->addToCompareAvailability = Bootstrap::getObjectManager()->get(AddToCompareAvailability::class);
        $this->storeManager = Bootstrap::getObjectManager()->get(StoreManagerInterface::class);
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
    }

    /**
     * Verify are products available for compare with custom source.
     *
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDbIsolation disabled
     * @return void
     */
    public function testIsAvailableForCompareWithCustomSource(): void
    {
        $this->storeManager->setCurrentStore('store_for_eu_website');
        $productInStock = $this->productRepository->get('SKU-1');
        $productOutOfStock = $this->productRepository->get('SKU-2');

        self::assertTrue($this->addToCompareAvailability->isAvailableForCompare($productInStock));
        self::assertFalse($this->addToCompareAvailability->isAvailableForCompare($productOutOfStock));
    }
}
