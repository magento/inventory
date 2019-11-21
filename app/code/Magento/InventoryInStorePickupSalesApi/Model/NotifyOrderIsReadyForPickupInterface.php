<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupSalesApi\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Send an email to the customer that order is ready to be picked up.
 *
 * @api
 */
interface NotifyOrderIsReadyForPickupInterface
{
    /**
     * Notify customer that the order is ready for pickup.
     *
     * @param int $orderId
     *
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(int $orderId): void;
}
