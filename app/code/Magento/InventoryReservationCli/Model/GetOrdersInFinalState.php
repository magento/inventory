<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Traversable;

/**
 * Get list of orders in any of the final states (Complete, Closed, Canceled).
 */
class GetOrdersInFinalState
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
     * @var GetCompleteOrderStatusList
     */
    private $getCompleteOrderStatusList;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param GetCompleteOrderStatusList $getCompleteOrderStatusList
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GetCompleteOrderStatusList $getCompleteOrderStatusList
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->getCompleteOrderStatusList = $getCompleteOrderStatusList;
    }

    /**
     * Get list of orders in any of the final states (Complete, Closed, Canceled).
     *
     * @param array $orderIds
     * @param int $bunchSize
     * @param int $page
     * @return Traversable|OrderInterface[]
     */
    public function execute(array $orderIds, int $bunchSize = 50, int $page = 1): Traversable
    {
        /** @var SearchCriteriaInterface $filter */
        $filter = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $orderIds, 'in')
            ->addFilter('state', $this->getCompleteOrderStatusList->execute(), 'in')
            ->setPageSize($bunchSize)
            ->setCurrentPage($page)
            ->create();

        $orderSearchResult = $this->orderRepository->getList($filter);

        foreach ($orderSearchResult->getItems() as $item) {
            yield $item->getEntityId() => $item;
        }

        gc_collect_cycles();
    }
}
