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
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationsAssignedToSalesChannelInterface;
use Magento\InventoryInStorePickupApi\Model\Mapper;
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
     * @var Mapper
     */
    private $mapper;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @param GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority
     * @param StockResolverInterface $stockResolver
     * @param Mapper $mapper
     */
    public function __construct(
        GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority,
        StockResolverInterface $stockResolver,
        Mapper $mapper
    ) {
        $this->getSourcesAssignedToStockOrderedByPriority = $getSourcesAssignedToStockOrderedByPriority;
        $this->stockResolver = $stockResolver;
        $this->mapper = $mapper;
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
            if ($source->getExtensionAttributes() && $source->getExtensionAttributes()->getIsPickupLocationActive()) {
                $result[] = $this->mapper->map($source);
            }
        }

        return $result;
    }
}
