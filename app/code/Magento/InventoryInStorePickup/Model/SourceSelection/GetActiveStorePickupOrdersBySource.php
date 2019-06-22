<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SourceSelection;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Gets list of orders placed by store pickup which are not complete yet
 */
class GetActiveStorePickupOrdersBySource
{
    private const PICKUP_LOCATION_CODE = 'pickup_location_code';

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var array
     */
    private $statesToFilter;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param array $statesToFilter
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $statesToFilter = []
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->statesToFilter = $statesToFilter;
    }

    /**
     * @param string $pickupLocationCode
     *
     * @return OrderSearchResultInterface
     */
    public function execute(string $pickupLocationCode): OrderSearchResultInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(self::PICKUP_LOCATION_CODE, $pickupLocationCode)
            ->addFilter(OrderInterface::STATE, implode(',', $this->statesToFilter), 'nin')
            ->create();

        return $this->orderRepository->getList($searchCriteria);
    }
}
