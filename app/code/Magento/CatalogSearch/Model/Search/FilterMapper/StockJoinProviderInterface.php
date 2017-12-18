<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogSearch\Model\Search\FilterMapper;

use Magento\Framework\DB\Select;

/**
 * SPI Interface to change the stock join
 */
interface StockJoinProviderInterface
{

    /**
     * Add stock join to the given select.
     *
     * @param Select $select
     * @param $alias
     * @return void
     */
    public function add(Select $select, $alias);
}
