<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupSalesAdminUi\Model;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface;

/**
 * Get list of sources marked as pickup location by stock
 */
class GetPickupSources
{
    /**
     * @var GetSourcesAssignedToStockOrderedByPriorityInterface
     */
    private $getSourcesAssignedToStockOrderedByPriority;

    /**
     * @param GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority
     */
    public function __construct(
        GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority
    ) {
        $this->getSourcesAssignedToStockOrderedByPriority = $getSourcesAssignedToStockOrderedByPriority;
    }

    /**
     * Get list of sources marked as pickup location by stock.
     *
     * @param int $stockId
     * @return array
     * @throws InputException
     * @throws LocalizedException
     */
    public function execute(int $stockId): array
    {
        $stockSources = $this->getSourcesAssignedToStockOrderedByPriority->execute($stockId);

        return array_filter(
            $stockSources,
            function (SourceInterface $source): bool {
                return $source->getExtensionAttributes() && (bool)$source->getExtensionAttributes()
                                                                         ->getIsPickupLocationActive();
            }
        );
    }
}
