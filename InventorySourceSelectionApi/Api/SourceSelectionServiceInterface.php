<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySourceSelectionApi\Api;

/**
 * Returns source selection algorithm result for given Inventory Request
 *
 * @api
 */
interface SourceSelectionServiceInterface
{
    /**
     * Executes the source selection service using the provided inventory request and algorithm code.
     *
     * @param \Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterface $inventoryRequest
     * @param string $algorithmCode
     * @return \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultInterface
     */
    public function execute(
        \Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterface $inventoryRequest,
        string $algorithmCode
    ): \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionResultInterface;
}
