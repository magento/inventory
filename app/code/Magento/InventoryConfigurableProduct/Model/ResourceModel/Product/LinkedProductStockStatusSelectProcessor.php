<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Model\ResourceModel\Product;

use Magento\Catalog\Model\ResourceModel\Product\BaseSelectProcessorInterface;
use Magento\Framework\DB\Select;

class LinkedProductStockStatusSelectProcessor implements BaseSelectProcessorInterface
{
    /**
     * @param Select $select
     * @return Select
     */
    public function process(Select $select): Select
    {
        return $select;
    }
}
