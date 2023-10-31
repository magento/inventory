<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesApi\Model;

use Magento\Framework\Exception\LocalizedException;

/**
 * Responsible for retrieving stock items data.
 *
 * @api
 */
interface GetStockItemsDataInterface
{
    /**
     * Constants for represent fields in result array
     */
    const SKU = 'sku';
    const QUANTITY = 'quantity';
    const IS_SALABLE = 'is_salable';
    /**#@-*/

    /**
     * Given array of product skus and a stock id, return stock items data.
     *
     * @param array $skus
     * @param int $stockId
     * @return array|null
     * @throws LocalizedException
     */
    public function execute(array $skus, int $stockId): ?array;
}
