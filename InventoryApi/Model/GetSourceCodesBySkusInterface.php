<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Model;

/**
 * Sugar service to retrieve source codes by a list of SKUs
 * @api
 */
interface GetSourceCodesBySkusInterface
{
    /**
     * Retrieves source codes associated with the provided list of SKUs and returns them as an array.
     *
     * @param array $skus
     * @return array
     */
    public function execute(array $skus): array;
}
