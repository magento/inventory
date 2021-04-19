<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Test\Integration\Model\ResourceModel\SourceItem;

use Magento\Inventory\Model\ResourceModel\SourceItem\DecrementQtyForMultipleSourceItem;
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
