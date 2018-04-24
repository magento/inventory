<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogAdminUi\Plugin\InventoryApi;

/**
 * Maintains consistency with Single-Source mode in case when no Source Item Quantity provided.
 */
class MaintainSourceItemStatusConsistencyPlugin
{
    /**
     * Set Status to 0 if no Quantity provided.
     *
     * @param SourceItemsSaveInterface $subject
     * @param \Magento\InventoryApi\Api\Data\SourceItemInterface[] $sourceItems
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeExecute(SourceItemsSaveInterface $subject, array $sourceItems): array
    {
        foreach ($sourceItems as $sourceItem) {
            if ('' === $sourceItem->getQuantity()) {
                $sourceItem->setStatus(0);
            }
        }

        return [$sourceItems];
    }
}
