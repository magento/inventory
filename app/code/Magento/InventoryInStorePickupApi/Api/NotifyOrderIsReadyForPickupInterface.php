<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickupApi\Exception\OrderIsNotReadyForPickupException;

interface NotifyOrderIsReadyForPickupInterface
{
    /**
     * @param int $orderId
     *
     * @throws NoSuchEntityException
     * @throws OrderIsNotReadyForPickupException
     * @throws LocalizedException
     */
    public function execute(int $orderId): void;
}
