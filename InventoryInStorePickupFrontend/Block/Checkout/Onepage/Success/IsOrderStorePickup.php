<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupFrontend\Block\Checkout\Onepage\Success;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\InventoryInStorePickupSalesApi\Model\IsStorePickupOrderInterface;

/**
 * Verify order has 'in store pickup' delivery method.
 */
class IsOrderStorePickup implements ArgumentInterface
{
    /**
     * @var IsStorePickupOrderInterface
     */
    private $isStorePickupOrder;

    /**
     * @var Session
     */
    private $session;

    /**
     * @param IsStorePickupOrderInterface $isStorePickupOrder
     * @param Session $session
     */
    public function __construct(IsStorePickupOrderInterface $isStorePickupOrder, Session $session)
    {
        $this->isStorePickupOrder = $isStorePickupOrder;
        $this->session = $session;
    }

    /**
     * Verify created order has 'in store pickup' delivery method.
     *
     * @return bool
     */
    public function execute(): bool
    {
        $order = $this->session->getLastRealOrder();
        return $this->isStorePickupOrder->execute((int)$order->getId());
    }
}
