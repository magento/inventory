<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\ResourceModel;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Inventory\Model\ResourceModel\SourceItem;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Adapt adding source code filter to collection.
 */
class AddSourceCodeFilterToCollection
{
    /**
     * Add source code filter to product collection
     *
     * @param Collection $collection
     * @param string|null $sourceCode
     * @return void
     */
    public function execute($collection, string $sourceCode = 'default')
    {
        $collection->getSelect()->join(
            ['inventory_source_item' => SourceItem::TABLE_NAME_SOURCE_ITEM],
            'e.sku = inventory_source_item.sku',
            []
        )->where('inventory_source_item.' . SourceItemInterface::SOURCE_CODE . ' = ?', $sourceCode);
    }
}
