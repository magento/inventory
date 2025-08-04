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
class VarcharFilter implements FilterProcessorInterface
{
    /**
     * Applies a "like" filter to the given column in the collection, matching values containing the specified input.
     *
     * @param Collection $collection
     * @param string $columnName
     * @param array|string $value
     * @return void
     */
    public function process(Collection $collection, string $columnName, $value): void
    {
        $collection->addFieldToFilter($columnName, ['like' => '%' . $value . '%']);
    }
}
