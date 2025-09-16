<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySourceSelectionApi\Api;

/**
 * Returns the list of Data Interfaces which represent registered SSA in the system
 *
 * @api
 */
interface GetSourceSelectionAlgorithmListInterface
{
    /**
     * Returns a list of registered Source Selection Algorithm (SSA) data interfaces available in the system.
     *
     * @return \Magento\InventorySourceSelectionApi\Api\Data\SourceSelectionAlgorithmInterface[]
     */
    public function execute(): array;
}
