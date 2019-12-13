<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryGroupedProduct\Test\Integration\Order;

use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;

/**
 * Test place order on inventory custom stock.
 */
class PlaceOrderOnCustomStockTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var SourceItemInterfaceFactory
     */
    private $sourceItemFactory;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        $this->sourceItemsSave = $this->objectManager->get(SourceItemsSaveInterface::class);
        $this->sourceItemFactory = $this->objectManager->get(SourceItemInterfaceFactory::class);
        $this->quoteFactory = $this->objectManager->get(QuoteFactory::class);
    }

    /**
     * Tests adding Grouped product without Source items in default source.
     *
     * @return void
     * @magentoDataFixture Magento/GroupedProduct/_files/product_grouped_with_simple_out_of_stock.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/change_stock_for_base_website.php
     * @magentoDbIsolation disabled
     */
    public function testAddGroupedProductWithoutItemsInDefaultSource(): void
    {
        $simple1Sku = 'simple_100000001';
        $simple2Sku = 'simple_100000002';
        $customStockSourceCode = 'source-code-1';

        $this->createSourceItem($customStockSourceCode, $simple1Sku, 11);
        $this->createSourceItem($customStockSourceCode, $simple2Sku, 12);

        $groupedProduct = $this->productRepository->get('grouped');
        $quote = $this->quoteFactory->create();
        $quote->addProduct($groupedProduct, 1);
        /** @var \Magento\Quote\Model\Quote\Item[] $items */
        $items = $quote->getAllItems();

        $this->assertCount(2, $items);
        $firstItem = $items[0];
        $secondItem = $items[1];
        $this->assertEquals($simple1Sku, $firstItem->getSku());
        $this->assertEquals($simple2Sku, $secondItem->getSku());
    }

    /**
     * Creates source item.
     *
     * @param string $sourceCode
     * @param string $sku
     * @param float $quantity
     * @param int $status
     * @return void
     */
    private function createSourceItem(
        string $sourceCode,
        string $sku,
        float $quantity,
        int $status = SourceItemInterface::STATUS_IN_STOCK
    ): void {
        $sourceItemParams = [
            'data' => [
                SourceItemInterface::SOURCE_CODE => $sourceCode,
                SourceItemInterface::SKU => $sku,
                SourceItemInterface::QUANTITY => $quantity,
                SourceItemInterface::STATUS => $status
            ]
        ];

        $sourceItem = $this->sourceItemFactory->create($sourceItemParams);
        $this->sourceItemsSave->execute([$sourceItem]);
    }
}
