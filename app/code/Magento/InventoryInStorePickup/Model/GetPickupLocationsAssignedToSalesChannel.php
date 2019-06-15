<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface;
use Magento\InventoryInStorePickup\Model\Source\GetIsPickupLocationActive;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationsAssignedToSalesChannelInterface;
use Magento\InventoryInStorePickupApi\Api\MapperInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * @inheritdoc
 */
class GetPickupLocationsAssignedToSalesChannel implements GetPickupLocationsAssignedToSalesChannelInterface
{
    /**
     * @var GetSourcesAssignedToStockOrderedByPriorityInterface
     */
    private $getSourcesAssignedToStockOrderedByPriority;

    /**
     * @var MapperInterface
     */
    private $mapper;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var GetIsPickupLocationActive
     */
    private $getIsPickupLocationActive;

    /**
     * @param GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority
     * @param StockResolverInterface $stockResolver
     * @param MapperInterface $mapper
     * @param Source\GetIsPickupLocationActive $getIsPickupLocationActive
     */
    public function __construct(
        GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority,
        StockResolverInterface $stockResolver,
        MapperInterface $mapper,
        GetIsPickupLocationActive $getIsPickupLocationActive
    ) {
        $this->getSourcesAssignedToStockOrderedByPriority = $getSourcesAssignedToStockOrderedByPriority;
        $this->stockResolver = $stockResolver;
        $this->mapper = $mapper;
        $this->getIsPickupLocationActive = $getIsPickupLocationActive;
    }

    /**
     * @inheritdoc
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(string $salesChannelType, string $salesChannelCode): array
    {
        $stock = $this->stockResolver->execute($salesChannelType, $salesChannelCode);
        $sources = $this->getSourcesAssignedToStockOrderedByPriority->execute($stock->getStockId());

        $result = [];
        foreach ($sources as $source) {
            if ($this->getIsPickupLocationActive->execute($source)) {
                $result[] = $this->mapper->map($source);
            }
        }

        return $result;
    }
}
