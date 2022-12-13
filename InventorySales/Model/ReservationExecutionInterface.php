<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

/**
 * Strategy interface to define if to defer placing inventory reservation or not.
 */
interface ReservationExecutionInterface
{
    /**
     * Defer to place inventory reservation or not.
     *
     * @return bool
     */
    public function isDeferred(): bool;
}
