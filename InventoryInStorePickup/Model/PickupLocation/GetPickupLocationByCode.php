<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\PickupLocation;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\MapperInterface;

/**
 * Get Pickup Location by its code
 */
class GetPickupLocationByCode
{
    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var MapperInterface
     */
    private $mapper;

    /**
     * @param SourceRepositoryInterface $sourceRepository
     * @param MapperInterface $mapper
     */
    public function __construct(
        SourceRepositoryInterface $sourceRepository,
        MapperInterface $mapper
    ) {
        $this->sourceRepository = $sourceRepository;
        $this->mapper = $mapper;
    }

    /**
     * @param string $pickupLocationCode
     *
     * @return PickupLocationInterface
     * @throws NoSuchEntityException
     */
    public function execute(string $pickupLocationCode): PickupLocationInterface
    {
        $source = $this->sourceRepository->get($pickupLocationCode);

        return $this->mapper->map($source);
    }
}
