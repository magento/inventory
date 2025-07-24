<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySourceSelectionApi\Model;

use Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterface;
use Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultInterface;

/**
 * Returns source selection algorithm result for given Inventory Request
 * Current interface should be implemented in order to add own Source Selection Method
 *
 * @api
 */
interface SourceSelectionInterface
{
    /**
     * Executes the source selection algorithm for a given inventory request, returning the selection result.
     *
     * @param InventoryRequestInterface $inventoryRequest
     * @return SourceSelectionResultInterface
     */
    public function execute(InventoryRequestInterface $inventoryRequest): SourceSelectionResultInterface;
}
