<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Model;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface;
use Magento\InventoryInStorePickup\Model\Source\GetIsPickupLocationActive;

/**
 * Get list of sources marked as pickup location by website
 */
class GetPickupSources
{
    /**
     * @var GetSourcesAssignedToStockOrderedByPriorityInterface
     */
    private $getSourcesAssignedToStockOrderedByPriority;

    /**
     * @var GetIsPickupLocationActive
     */
    private $getIsPickupLocationActive;

    /**
     * @param GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority
     * @param GetIsPickupLocationActive $getIsPickupLocationActive
     */
    public function __construct(
        GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority,
        GetIsPickupLocationActive $getIsPickupLocationActive
    ) {

        $this->getSourcesAssignedToStockOrderedByPriority = $getSourcesAssignedToStockOrderedByPriority;
        $this->getIsPickupLocationActive = $getIsPickupLocationActive;
    }

    /**
     * Get list of sources marked as pickup location by website.
     *
     * @param int $stockId
     * @return array
     * @throws InputException
     * @throws LocalizedException
     */
    public function execute(int $stockId): array
    {
        $stockSources = $this->getSourcesAssignedToStockOrderedByPriority->execute($stockId);

        return array_filter($stockSources, [$this->getIsPickupLocationActive, 'execute']);
    }
}
