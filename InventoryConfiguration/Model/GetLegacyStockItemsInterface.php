<?php
/**
 * Copyright 2024 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\Exception\LocalizedException;

interface GetLegacyStockItemsInterface
{
    /**
     * Get legacy stock items entity by skus.
     *
     * @param string[] $skus
     * @return StockItemInterface[]
     * @throws LocalizedException
     */
    public function execute(array $skus): array;
}
