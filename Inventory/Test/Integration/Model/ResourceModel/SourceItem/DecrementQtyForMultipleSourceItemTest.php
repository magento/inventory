<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Test\Integration\Model\ResourceModel\SourceItem;

use Magento\Inventory\Model\ResourceModel\SourceItem\DecrementQtyForMultipleSourceItem;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class DecrementQtyForMultipleSourceItemTest extends TestCase
{
    /**
     * @var DecrementQtyForMultipleSourceItem
     */
    private $decrementQtyForMultipleSourceItem;

    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemBySku;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->decrementQtyForMultipleSourceItem = $objectManager->get(DecrementQtyForMultipleSourceItem::class);
        $this->getSourceItemBySku = $objectManager->get(GetSourceItemsBySkuInterface::class);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     */
    public function testExecute()
    {
        //Assert quantity of source item before decrement
        $this->assertSourceItemQuantity(5.5, 5);

        $firstSourceItem = current($this->getSourceItemBySku->execute('SKU-1'));
        $secondSourceItem = current($this->getSourceItemBySku->execute('SKU-2'));
        $decrementItems = [
            [
                'source_item' => $firstSourceItem,
                'qty_to_decrement' => 1
            ],
            [
                'source_item' => $secondSourceItem,
                'qty_to_decrement' => 2
            ]
        ];
        $this->decrementQtyForMultipleSourceItem->execute($decrementItems);

        //Assert quantity of source item after decrement
        $this->assertSourceItemQuantity(4.5, 3);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     */
    public function testExecuteSetsOutOfStockStatusAtThreshold()
    {
        $decrementItems = [
            // reaches the threshold
            ['source_item' => $this->getSourceItem('SKU-2', 'us-1'), 'qty_to_decrement' => 5,
                'out_of_stock_threshold' => 0],
            // stays above the threshold
            ['source_item' => $this->getSourceItem('SKU-1', 'eu-1'), 'qty_to_decrement' => 1,
                'out_of_stock_threshold' => 0],
            // the quantity before the decrement (6) is above the threshold, the new one (5) is not
            ['source_item' => $this->getSourceItem('SKU-4', 'eu-2'), 'qty_to_decrement' => 1,
                'out_of_stock_threshold' => 5],
            // an out of stock source item is not set back in stock
            ['source_item' => $this->getSourceItem('SKU-3', 'eu-2'), 'qty_to_decrement' => 1,
                'out_of_stock_threshold' => 0],
            // no threshold, status is left untouched
            ['source_item' => $this->getSourceItem('SKU-1', 'eu-2'), 'qty_to_decrement' => 3],
        ];
        $this->decrementQtyForMultipleSourceItem->execute($decrementItems);

        $this->assertSourceItem('SKU-2', 'us-1', 0, SourceItemInterface::STATUS_OUT_OF_STOCK);
        $this->assertSourceItem('SKU-1', 'eu-1', 4.5, SourceItemInterface::STATUS_IN_STOCK);
        $this->assertSourceItem('SKU-4', 'eu-2', 5, SourceItemInterface::STATUS_OUT_OF_STOCK);
        $this->assertSourceItem('SKU-3', 'eu-2', 5, SourceItemInterface::STATUS_OUT_OF_STOCK);
        $this->assertSourceItem('SKU-1', 'eu-2', 0, SourceItemInterface::STATUS_IN_STOCK);
    }

    /**
     * Get source item by sku and source code
     *
     * @param string $sku
     * @param string $sourceCode
     * @return SourceItemInterface
     */
    private function getSourceItem(string $sku, string $sourceCode): SourceItemInterface
    {
        foreach ($this->getSourceItemBySku->execute($sku) as $sourceItem) {
            if ($sourceItem->getSourceCode() === $sourceCode) {
                return $sourceItem;
            }
        }
        self::fail(sprintf('Source item "%s" not found for source "%s"', $sku, $sourceCode));
    }

    /**
     * Assert quantity and status of source item
     *
     * @param string $sku
     * @param string $sourceCode
     * @param float $expectedQty
     * @param int $expectedStatus
     */
    private function assertSourceItem(string $sku, string $sourceCode, float $expectedQty, int $expectedStatus): void
    {
        $sourceItem = $this->getSourceItem($sku, $sourceCode);
        self::assertEquals($expectedQty, $sourceItem->getQuantity());
        self::assertEquals($expectedStatus, $sourceItem->getStatus());
    }

    /**
     * Assert quantity of source item
     *
     * @param float $firstItemExpectedQty
     * @param float $secondItemExpectedQty
     */
    private function assertSourceItemQuantity(float $firstItemExpectedQty, float $secondItemExpectedQty): void
    {
        $firstSourceItemQty = current($this->getSourceItemBySku->execute('SKU-1'))->getQuantity();
        $secondSourceItemQty = current($this->getSourceItemBySku->execute('SKU-2'))->getQuantity();
        self::assertEquals($firstItemExpectedQty, $firstSourceItemQty);
        self::assertEquals($secondItemExpectedQty, $secondSourceItemQty);
    }
}
