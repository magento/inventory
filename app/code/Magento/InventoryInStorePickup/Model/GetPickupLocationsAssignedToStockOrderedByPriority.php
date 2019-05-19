<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface;
use Magento\InventoryInStorePickupApi\Model\Mapper;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationsAssignedToStockOrderedByPriorityInterface;

/**
 * @inheritdoc
 */
class GetPickupLocationsAssignedToStockOrderedByPriority implements GetPickupLocationsAssignedToStockOrderedByPriorityInterface
{
    /**
     * @var GetSourcesAssignedToStockOrderedByPriorityInterface
     */
    private $getSourcesAssignedToStockOrderedByPriority;

    /**
     * @var Mapper
     */
    private $mapper;

    /**
     * @param GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority
     * @param Mapper $mapper
     */
    public function __construct(
        GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority,
        Mapper $mapper
    ) {
        $this->getSourcesAssignedToStockOrderedByPriority = $getSourcesAssignedToStockOrderedByPriority;
        $this->mapper = $mapper;
    }

    /**
     * @inheritdoc
     * @throws LocalizedException
     */
    public function execute(int $stockId): array
    {
        $sources = $this->getSourcesAssignedToStockOrderedByPriority->execute($stockId);

        $result = [];
        foreach ($sources as $source) {
            if ($source->getExtensionAttributes() && $source->getExtensionAttributes()->getIsPickupLocationActive()) {
                $result[] = $this->mapper->map($source);
            }
        }

        return $result;
    }
}
