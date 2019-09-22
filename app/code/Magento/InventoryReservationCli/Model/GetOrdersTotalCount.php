<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Retrieve the total count of orders
 */
class GetOrdersTotalCount
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Retrieves total count of orders
     *
     * @return int
     */
    public function execute(): int
    {
        /** @var SearchCriteriaInterface $filter */
        $filter = $this->searchCriteriaBuilder
            ->setPageSize(1)
            ->create();
        $orderSearchResult = $this->orderRepository->getList($filter);
        return $orderSearchResult->getTotalCount();
    }
}
