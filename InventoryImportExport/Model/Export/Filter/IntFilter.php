<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Model\Export\Filter;

use Magento\Inventory\Model\ResourceModel\SourceItem\Collection;
use Magento\InventoryImportExport\Model\Export\FilterProcessorInterface;

/**
 * @inheritdoc
 */
class IntFilter implements FilterProcessorInterface
{
    /**
     * Filters a collection by applying range or exact match conditions to the specified column based on the input value
     *
     * @param Collection $collection
     * @param string $columnName
     * @param array|string $value
     * @return void
     */
    public function process(Collection $collection, string $columnName, $value): void
    {
        if (is_array($value)) {
            $from = $value[0] ?? null;
            $to = $value[1] ?? null;

            if (is_numeric($from) && !empty($from)) {
                $collection->addFieldToFilter($columnName, ['from' => $from]);
            }

            if (is_numeric($to) && !empty($to)) {
                $collection->addFieldToFilter($columnName, ['to' => $to]);
            }

            return;
        }

        $collection->addFieldToFilter($columnName, ['eq' => $value]);
    }
}
