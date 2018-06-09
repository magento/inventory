<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Inventory\Model\Stock\SearchCriteria\CollectionProcessor\StockFilterProcessor;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\FilterProcessor\CustomFilterInterface;
use Magento\Framework\Data\Collection\AbstractDb;

class StockNameFilter implements CustomFilterInterface
{

    /**
     * Apply Custom Filter to Collection
     *
     * @param Filter $filter
     * @param AbstractDb $collection
     * @return bool Whether the filter was applied
     * @since 100.2.0
     */
    public function apply(Filter $filter, AbstractDb $collection)
    {
        $conditionType = $filter->getConditionType();
        $value = $filter->getValue();

        if ($conditionType === 'fulltext') {
            $conditionType = 'like';
            $value = '%' . $value . '%';
        }
        $nameFilter = [$conditionType => [$value]];

        $collection->addFieldToFilter($filter->getField(), $nameFilter);

        return true;
    }
}
