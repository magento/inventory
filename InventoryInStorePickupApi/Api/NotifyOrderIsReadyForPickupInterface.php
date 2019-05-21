<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Send an email to the customer and ship the order to reserve pickup location`s QTY
 */
interface NotifyOrderIsReadyForPickupInterface
{
    /**
     * Send an email to the customer and ship the order to reserve pickup location`s QTY.
     *
     * @param int $orderId
     *
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(int $orderId): void;
}
