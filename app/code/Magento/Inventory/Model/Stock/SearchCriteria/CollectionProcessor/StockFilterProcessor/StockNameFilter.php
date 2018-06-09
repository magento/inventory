<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

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
     * @return bool
     */
    public function apply(Filter $filter, AbstractDb $collection): bool
    {
        $conditionType = $filter->getConditionType();
        $value = $filter->getValue();

        if ($conditionType === 'fulltext') {
            $conditionType = 'like';
            $value = '%' . $value . '%';
        }
        $fieldFilter = [$conditionType => [$value]];

        $collection->addFieldToFilter($filter->getField(), $fieldFilter);

        return true;
    }
}
