<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Model;

use Magento\InventoryInStorePickupApi\Api\IsStorePickupOrderInterface;
use Magento\Sales\Model\Order;

/**
 * Check if 'Notify Order is Ready for Pickup' button should be rendered
 */
class IsRenderReadyForPickupButton
{
    /**
     * @var IsStorePickupOrderInterface
     */
    private $isStorePickupOrder;

    /**
     * @param IsStorePickupOrderInterface $isStorePickupOrder
     */
    public function __construct(
        IsStorePickupOrderInterface $isStorePickupOrder
    ) {
        $this->isStorePickupOrder = $isStorePickupOrder;
    }

    /**
     * @param Order $order
     *
     * @return bool
     */
    public function execute(Order $order): bool
    {
        return $this->isStorePickupOrder->execute((int)$order->getEntityId()) && $order->canShip();
    }
}
