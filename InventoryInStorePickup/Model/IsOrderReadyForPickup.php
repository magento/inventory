<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\InventoryInStorePickup\Model\Order\IsFulfilled;
use Magento\InventoryInStorePickupApi\Api\IsOrderReadyForPickupInterface;

class IsOrderReadyForPickup implements IsOrderReadyForPickupInterface
{
    /**
     * @var \Magento\InventoryInStorePickup\Model\Order\IsFulfilled
     */
    private $isFulfilled;

    /**
     * IsReadyForPickup constructor.
     *
     * @param \Magento\InventoryInStorePickup\Model\Order\IsFulfilled $isFulfilled
     */
    public function __construct(
        IsFulfilled $isFulfilled
    ) {
        $this->isFulfilled = $isFulfilled;
    }

    /**
     * @param int $orderId
     *
     * @return bool
     */
    public function execute(int $orderId):bool
    {
        return $this->isFulfilled->execute($orderId);
    }
}
