<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\SalesQuoteItem;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventoryReservationsApi\Model\CleanupReservationsInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddSalesQuoteItemOnNotDefaultStockTest extends TestCase
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StockRepositoryInterface
     */
    private $stockRepository;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var CleanupReservationsInterface
     */
    private $cleanupReservations;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->cleanupReservations = Bootstrap::getObjectManager()->get(CleanupReservationsInterface::class);
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->stockRepository = Bootstrap::getObjectManager()->get(StockRepositoryInterface::class);
        $this->storeRepository = Bootstrap::getObjectManager()->get(StoreRepositoryInterface::class);
        $this->storeManager = Bootstrap::getObjectManager()->get(StoreManagerInterface::class);
        $this->cleanupReservations->execute();
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @param string $sku
     * @param int $stockId
     * @param float $qty
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     *
     * @dataProvider productsInStockDataProvider
     *
     * @magentoDbIsolation disabled
     */
    public function testAddInStockProductToQuote(
        string $sku,
        int $stockId,
        float $qty
    ) {
        $quote = $this->getQuote($stockId);
        $product = $this->getProductBySku($sku);

        $quote->addProduct($product, $qty);

        /** @var CartItemInterface $quoteItem */
        $quoteItem = current($quote->getAllItems());
        self::assertEquals($qty, $quoteItem->getQty());
    }

    /**
     * @see ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @return array
     */
    public function productsInStockDataProvider(): array
    {
        return [
            ['SKU-1', 10, 4],
            ['SKU-1', 10, 2],
            ['SKU-2', 30, 3],
            ['SKU-2', 30, 1]
        ];
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @param string $sku
     * @param int $stockId
     * @param float $qty
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     *
     * @dataProvider notSalableProductsDataProvider
     *
     * @magentoDbIsolation disabled
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function testAddOutOffStockProductToQuote(
        string $sku,
        int $stockId,
        float $qty,
        string $message,
        float $backorderqty
    ) {
        $quote = $this->getQuote($stockId);
        $product = $this->getProductBySku($sku);

        self::expectException(LocalizedException::class);
        $quote->addProduct($product, $qty);

        $quoteItemCount = count($quote->getAllItems());
        self::assertEquals(0, $quoteItemCount);
    }

    /**
     * @magentoDataFixture ../../../../InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoDataFixture ../../../../InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture ../../../../InventoryIndexer/Test/_files/reindex_inventory.php
     * @magentoConfigFixture current_store cataloginventory/item_options/backorders 2
     *
     * @param string $sku
     * @param int $stockId
     * @param float $qty
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     *
     * @dataProvider notSalableProductsDataProvider
     *
     * @magentoDbIsolation disabled
     */
    public function testAddOutOffStockProductToQuoteWithBackorderNotify(
        string $sku,
        int $stockId,
        float $qty,
        string $message,
        float $backorderqty
    ) {
        $quote = $this->getQuote($stockId);
        $product = $this->getProductBySku($sku);

        self::expectException(LocalizedException::class);
        $quote->addProduct($product, $qty);

        $quoteItem = current($quote->getAllItems());

        self::assertEquals($qty, $quoteItem->getQty());
        self::assertEquals(
            $message,
            $quote->getItemsCollection()->getFirstItem()->getStockStateResult()->getMessage()
        );
        self::assertEquals(
            $backorderqty,
            $quote->getItemsCollection()->getFirstItem()->getStockStateResult()->getItemBackorders()
        );
    }

    /**
     * @see ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @return array
     */
    public function notSalableProductsDataProvider(): array
    {
        return [
            [
                'SKU-1',
                20,
                6,
                "We don't have as many quantity as you requested, but we'll back order the remaining 3.",
                3
            ],
            [
                'SKU-1',
                30,
                9,
                "We don't have as many quantity as you requested, but we'll back order the remaining 10.",
                10
            ],
            [
                'SKU-2',
                10,
                1.5,
                "We don't have as many quantity as you requested, but we'll back order the remaining 1.5.",
                1.5
            ],
            [
                'SKU-2',
                30,
                5.5,
                "We don't have as many quantity as you requested, but we'll back order the remaining 0.5.",
                0.5
            ],
            [
                'SKU-3',
                20,
                1.9,
                "We don't have as many quantity as you requested, but we'll back order the remaining 1.9.",
                1.9
            ]
        ];
    }

    /**
     * @param string $sku
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function getProductBySku(string $sku): ProductInterface
    {
        return $this->productRepository->get($sku);
    }

    /**
     * @param int $stockId
     * @return Quote
     * @throws NoSuchEntityException
     */
    private function getQuote(int $stockId): Quote
    {
        /** @var StockInterface $stock */
        $stock = $this->stockRepository->get($stockId);
        /** @var SalesChannelInterface[] $salesChannels */
        $salesChannels = $stock->getExtensionAttributes()->getSalesChannels();
        $storeCode = 'store_for_';
        foreach ($salesChannels as $salesChannel) {
            if ($salesChannel->getType() == SalesChannelInterface::TYPE_WEBSITE) {
                $storeCode .= $salesChannel->getCode();
                break;
            }
        }
        /** @var StoreInterface $store */
        $store = $this->storeRepository->get($storeCode);
        $this->storeManager->setCurrentStore($storeCode);
        return Bootstrap::getObjectManager()->create(
            Quote::class,
            [
                'data' => [
                    'store_id' => $store->getId(),
                    'is_active' => 0,
                    'is_multi_shipping' => 0,
                    'id' => 1
                ]
            ]
        );
    }

    protected function tearDown()
    {
        $this->cleanupReservations->execute();
    }
}
