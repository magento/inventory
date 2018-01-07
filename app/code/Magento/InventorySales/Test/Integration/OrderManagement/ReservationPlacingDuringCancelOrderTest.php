<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\StockManagement;

use Magento\InventoryApi\Api\GetProductQuantityInStockInterface;
use Magento\Sales\Api\OrderManagementInterface;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;

class ReservationPlacingDuringCancelOrderTest extends TestCase
{
    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var GetProductQuantityInStockInterface
     */
    private $getProductQtyInStock;

    protected function setUp()
    {
        $this->orderManagement = Bootstrap::getObjectManager()->get(OrderManagementInterface::class);
        $this->getProductQtyInStock = Bootstrap::getObjectManager()->get(GetProductQuantityInStockInterface::class);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/order.php
     */
    public function testCancelOrder()
    {
        $orderId = 1;
        $orderItemSku = 'SKU-1';
        $stockId = 1;

        self::assertEquals(0, $this->getProductQtyInStock->execute($orderItemSku, $stockId));
        $this->orderManagement->cancel($orderId);
        self::assertEquals(12, $this->getProductQtyInStock->execute($orderItemSku, $stockId));
    }
}
