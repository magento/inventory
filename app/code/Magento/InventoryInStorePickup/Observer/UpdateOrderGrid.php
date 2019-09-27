<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\GridInterface;

/**
 * Update the order grid when order was placed with Pickup Location Code.
 */
class UpdateOrderGrid implements ObserverInterface
{
    /**
     * @var GridInterface
     */
    private $entityGrid;

    /**
     * @var ScopeConfigInterface
     */
    private $globalConfig;

    /**
     * @param GridInterface $entityGrid
     * @param ScopeConfigInterface $globalConfig
     */
    public function __construct(
        GridInterface $entityGrid,
        ScopeConfigInterface $globalConfig
    ) {
        $this->entityGrid = $entityGrid;
        $this->globalConfig = $globalConfig;
    }

    /**
     * Update the Order Grid in case Pickup Location was added to the order.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->globalConfig->getValue('dev/grid/async_indexing')) {
            /** @var OrderInterface $order */
            $order = $observer->getOrder();

            if ($order->getExtensionAttributes() && $order->getExtensionAttributes()->getPickupLocationCode()) {
                $this->entityGrid->refresh($order->getId());
            }
        }
    }
}
