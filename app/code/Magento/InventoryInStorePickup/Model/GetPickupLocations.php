<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface;
use Magento\InventoryInStorePickup\Model\PickupLocation\Mapper;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationsInterface;

/**
 * @inheritdoc
 */
class GetPickupLocations implements GetPickupLocationsInterface
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
     * GetPickupLocationsAssignedToStockOrderedByPriority constructor.
     *
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
     * @throws \Magento\Framework\Exception\LocalizedException
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
