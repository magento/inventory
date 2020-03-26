<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryShippingAdminUi\Test\Integration\Model;

use Magento\InventoryShippingAdminUi\Model\IsOrderSourceManageable;
use Magento\Sales\Model\OrderFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Verify IsOrderSourceManageable service.
 */
class IsOrderSourceManageableTest extends TestCase
{
    /**
     * Test subject.
     *
     * @var IsOrderSourceManageable
     */
    private $isOrderSourceManageable;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->isOrderSourceManageable = Bootstrap::getObjectManager()->get(IsOrderSourceManageable::class);
    }

    /**
     * Verify order with not assigned to all stocks items won't throw exception.
     *
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture Magento/Sales/_files/order.php
     *
     * @magentoDbIsolation disabled
     */
    public function testOrderWithProductsNotAssignedToStocks(): void
    {
        $order = Bootstrap::getObjectManager()->get(OrderFactory::class)->create()->loadByIncrementId('100000001');
        $result = $this->isOrderSourceManageable->execute($order);
        self::assertFalse($result);
    }
}
