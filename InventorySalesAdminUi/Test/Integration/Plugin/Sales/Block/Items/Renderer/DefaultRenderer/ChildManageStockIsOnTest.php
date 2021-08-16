<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Test\Integration\Plugin\Sales\Block\Items\Renderer\DefaultRenderer;

use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Block\Adminhtml\Items\Renderer\DefaultRenderer;
use Magento\InventorySalesAdminUi\Plugin\Sales\Block\Items\Renderer\DefaultRenderer\ChildManageStockIsOn;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test for ChildManageStockIsOn plugin class
 */
class ChildManageStockIsOnTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CreditmemoFactory
     */
    private $creditmemoFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->creditmemoFactory = $this->objectManager->create(CreditmemoFactory::class);
    }

    /**
     * Test configurable product with Manage Stock off but child product with Manage Stock on
     *
     * @magentoDataFixture Magento_InventorySalesAdminUi::Test/_files/order_with_configurable_product_and_manage_stock_off.php
     */
    public function testAfterCanReturnItemToStock()
    {
        /** @var Order $order */
        $order = $this->objectManager->create(Order::class);
        $order->loadByIncrementId('100000001');
        $creditmemo = $this->creditmemoFactory->createByOrder($order);
        $item = current($creditmemo->getItems());
        /** @var DefaultRenderer $defaultRenderer */
        $defaultRenderer = $this->objectManager->create(DefaultRenderer::class);
        /** @var ChildManageStockIsOn $childManageStockIsOn */
        $childManageStockIsOn = $this->objectManager->create(ChildManageStockIsOn::class);
        $this->assertTrue($childManageStockIsOn->afterCanReturnItemToStock($defaultRenderer, false, $item));
    }
}
