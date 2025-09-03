<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */

namespace Magento\InventorySourceSelectionApi\Model;

use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Get source item qty available for usage in SSA
 *
 * @api
 */
interface GetSourceItemQtyAvailableInterface
{
    /**
     * Gets the available quantity of a source item for use in Source Selection Algorithm (SSA).
     *
     * @param SourceItemInterface $sourceItem
     *
     * @return float
     */
    public function execute(SourceItemInterface $sourceItem): float;
}
