<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Model\SalableQuantityInconsistency;

use Magento\InventoryReservationCli\Model\ResourceModel\GetOrderDataForOrderInFinalState;
use Magento\InventoryReservationCli\Model\StoreWebsiteResolver;

/**
 * Match completed orders with unresolved reservations
 */
class AddCompletedOrdersToForUnresolvedReservations
{
    /**
     * @var GetOrderDataForOrderInFinalState
     */
    private $getOrderDataForOrderInFinalState;
    /**
     * @var StoreWebsiteResolver
     */
    private $storeWebsiteResolver;

    /**
     * @param GetOrderDataForOrderInFinalState $getOrderDataForOrderInFinalState
     * @param StoreWebsiteResolver $storeWebsiteResolver
     */
    public function __construct(
        GetOrderDataForOrderInFinalState $getOrderDataForOrderInFinalState,
        StoreWebsiteResolver $storeWebsiteResolver
    ) {
        $this->getOrderDataForOrderInFinalState = $getOrderDataForOrderInFinalState;
        $this->storeWebsiteResolver = $storeWebsiteResolver;
    }

    /**
     * Remove all entries without order
     *
     * @param Collector $collector
     */
    public function execute(Collector $collector): void
    {
        $inconsistencies = $collector->getItems();

        $orderIds = [];
        foreach ($inconsistencies as $inconsistency) {
            $orderIds[] = $inconsistency->getObjectId();
        }

        foreach ($this->getOrderDataForOrderInFinalState->execute($orderIds) as $orderData) {
            $websiteId = $this->storeWebsiteResolver->execute((int) $orderData['store_id']);
            $collector->addOrderData($orderData + ['website_id' => $websiteId]);
        }

        $collector->setItems($inconsistencies);
    }
}
