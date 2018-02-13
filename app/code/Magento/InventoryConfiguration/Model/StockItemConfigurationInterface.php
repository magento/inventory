<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

/**
 * Interface StockConfigurationInterface
 *
 * @api
 */
interface StockItemConfigurationInterface
{
    public function execute(string $sku, int $stockId, float $qtyWithReservation, bool $isSalable): bool;
}
