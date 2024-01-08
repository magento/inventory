<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Controller\Adminhtml\Product\Initialization\StockDataFilter;

use Magento\Catalog\Controller\Adminhtml\Product\Initialization\StockDataFilter;

class StockDataFilterPlugin
{
    /**
     * Allow min_qty to be assigned a value below 0.
     *
     * @param StockDataFilter $subject
     * @param array $result
     * @param array $stockData
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterFilter(
        StockDataFilter $subject,
        array $result,
        array $stockData
    ) {
        if (!isset($stockData['qty'])) {
            $result['qty'] = 0;
        }

        if (isset($stockData['min_qty'])) {
            $result['min_qty'] = $stockData['min_qty'];
        }
        return $result;
    }
}
