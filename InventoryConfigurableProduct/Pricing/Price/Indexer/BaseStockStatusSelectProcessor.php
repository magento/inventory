<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Pricing\Price\Indexer;

use Magento\Catalog\Model\ResourceModel\Product\BaseSelectProcessorInterface;
use Magento\Framework\DB\Select;

/**
 * Base select processor.
 */
class BaseStockStatusSelectProcessor implements BaseSelectProcessorInterface
{
    /**
     * Improves the select with stock status sub query.
     *
     * @param Select $select
     * @return Select
     */
    public function process(Select $select)
    {
        return $select;
    }
}
