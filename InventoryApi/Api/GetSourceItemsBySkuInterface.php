<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Api;

/**
 * Sugar service for find SourceItems by SKU
 *
 * @api
 */
interface GetSourceItemsBySkuInterface
{
    /**
     * Finds and returns source items associated with the given SKU as an array of `SourceItemInterface` objects.
     *
     * @param string $sku
     * @return \Magento\InventoryApi\Api\Data\SourceItemInterface[]
     */
    public function execute(string $sku): array;
}
