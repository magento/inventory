<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api;

interface NotifyOrderIsReadyAndShipInterface
{
    /**
     * @param int $orderId
     *
     * @return int Id of created Shipment
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\InventoryInStorePickupApi\Exception\OrderIsNotReadyForPickupException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(int $orderId):int;
}
