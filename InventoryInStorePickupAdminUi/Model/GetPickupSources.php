<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Model;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface;
use Magento\InventoryInStorePickup\Model\Source\GetIsPickupLocationActive;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * Get list of sources marked as pickup location by website
 */
class GetPickupSources
{
    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var GetSourcesAssignedToStockOrderedByPriorityInterface
     */
    private $getSourcesAssignedToStockOrderedByPriority;

    /**
     * @var GetIsPickupLocationActive
     */
    private $getIsPickupLocationActive;

    /**
     * @param StockResolverInterface $stockResolver
     * @param GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority
     * @param GetIsPickupLocationActive $getIsPickupLocationActive
     */
    public function __construct(
        StockResolverInterface $stockResolver,
        GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStockOrderedByPriority,
        GetIsPickupLocationActive $getIsPickupLocationActive
    ) {

        $this->stockResolver = $stockResolver;
        $this->getSourcesAssignedToStockOrderedByPriority = $getSourcesAssignedToStockOrderedByPriority;
        $this->getIsPickupLocationActive = $getIsPickupLocationActive;
    }

    /**
     * @param string $websiteCode
     *
     * @return array
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(string $websiteCode): array
    {
        $stockSources = $this->getSourcesAssignedToStockOrderedByPriority->execute(
            $this->stockResolver->execute(
                SalesChannelInterface::TYPE_WEBSITE,
                $websiteCode
            )->getStockId()
        );

        return array_filter($stockSources, [$this->getIsPickupLocationActive, 'execute']);
    }
}
