<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryInStorePickup\Model\Source\GetIsPickupLocationActive;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\GetPickupLocationInterface;
use Magento\InventoryInStorePickupApi\Model\Mapper;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * @inheritdoc
 */
class GetPickupLocation implements GetPickupLocationInterface
{
    private const PICKUP_LOCATION_NOT_FOUND = 'Can not find Pickup Location with code %1 for %2 Sales Channel "%3".';

    /**
     * @var GetIsPickupLocationActive
     */
    private $getIsPickupLocationActive;

    /**
     * @var GetStockSourceLinksInterface
     */
    private $getStockSourceLinks;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var Mapper
     */
    private $mapper;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @param GetIsPickupLocationActive $getIsPickupLocationActive
     * @param GetStockSourceLinksInterface $getStockSourceLinks
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param StockResolverInterface $stockResolver
     * @param Mapper $mapper
     * @param SourceRepositoryInterface $sourceRepository
     */
    public function __construct(
        GetIsPickupLocationActive $getIsPickupLocationActive,
        GetStockSourceLinksInterface $getStockSourceLinks,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StockResolverInterface $stockResolver,
        Mapper $mapper,
        SourceRepositoryInterface $sourceRepository
    ) {
        $this->getIsPickupLocationActive = $getIsPickupLocationActive;
        $this->getStockSourceLinks = $getStockSourceLinks;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->stockResolver = $stockResolver;
        $this->mapper = $mapper;
        $this->sourceRepository = $sourceRepository;
    }

    /**
     * @inheritdoc
     */
    public function execute(
        string $pickupLocationCode,
        string $salesChannelType,
        string $salesChannelCode
    ): PickupLocationInterface {
        $source = $this->sourceRepository->get($pickupLocationCode);

        if (!$this->getIsPickupLocationActive->execute($source)) {
            throw new NoSuchEntityException(
                __(
                    self::PICKUP_LOCATION_NOT_FOUND,
                    [$pickupLocationCode, $salesChannelType, $salesChannelCode]
                )
            );
        }

        $stock = $this->stockResolver->execute($salesChannelType, $salesChannelCode);

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(StockSourceLinkInterface::STOCK_ID, $stock->getStockId())
            ->addFilter(StockSourceLinkInterface::SOURCE_CODE, $pickupLocationCode)
            ->create();
        $result = $this->getStockSourceLinks->execute($searchCriteria);

        if ($result->getTotalCount() === 0) {
            throw new NoSuchEntityException(
                __(
                    self::PICKUP_LOCATION_NOT_FOUND,
                    [$pickupLocationCode, $salesChannelType, $salesChannelCode]
                )
            );
        }

        return $this->mapper->map($source);
    }
}
