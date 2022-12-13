<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
