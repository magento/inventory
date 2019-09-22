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
use Magento\Sales\Model\Order;

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
     * @return OrderInterface[]
     */
    public function execute(array $orderIds): \Traversable
    {
        $bunchSize = 50;
        $maxPage = $this->getMaxPage(count($orderIds), $bunchSize);
        for ($page = 1; $page <= $maxPage; $page++) {
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

    /**
     * Calculates max page
     *
     * @param int $totalCount
     * @param int $bunchSize
     * @return int
     */
    private function getMaxPage(int $totalCount, int $bunchSize): int
    {
        return (int)ceil($totalCount / $bunchSize);
    }
}
