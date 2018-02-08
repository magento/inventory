<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

interface StockConfigurationInterface
{
    public function validate($sku, $stockId, $qtyWithReservation, $isSalable, $globalMinQty) : bool;
}
