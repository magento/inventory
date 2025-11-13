<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Model;

interface GetStockIdsBySkusInterface
{
    /**
     * Retrieve stock ids by a list of SKUs
     *
     * @param array $skus
     * @return array
     */
    public function execute(array $skus): array;
}
