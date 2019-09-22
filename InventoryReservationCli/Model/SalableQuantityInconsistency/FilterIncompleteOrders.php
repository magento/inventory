<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Model\SalableQuantityInconsistency;

use Magento\InventoryReservationCli\Model\GetCompleteOrderStateList;
use Magento\InventoryReservationCli\Model\SalableQuantityInconsistency;

/**
 * Remove all reservations with incomplete state
 */
class FilterIncompleteOrders
{
    /**
     * @var GetCompleteOrderStateList
     */
    private $getCompleteOrderStatusList;

    /**
     * @param GetCompleteOrderStateList $getCompleteOrderStatusList
     */
    public function __construct(
        GetCompleteOrderStateList $getCompleteOrderStatusList
    ) {
        $this->getCompleteOrderStatusList = $getCompleteOrderStatusList;
    }

    /**
     * Remove all reservations with incomplete state
     *
     * @param SalableQuantityInconsistency[] $inconsistencies
     * @return SalableQuantityInconsistency[]
     */
    public function execute(array $inconsistencies): array
    {
        return array_filter(
            $inconsistencies,
            function (SalableQuantityInconsistency $inconsistency) {
                return !in_array($inconsistency->getOrderStatus(), $this->getCompleteOrderStatusList->execute());
            }
        );
    }
}
