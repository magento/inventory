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
 * Get list of orders, which are not in any of the final states (Complete, Closed, Canceled).
 */
class GetOrdersInNotFinalState
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
     * Get list of orders
     *
     * @return OrderInterface[]
     */
    public function execute(): \Traversable
    {
        $bunchSize = 50;
        $maxPage = $this->getMaxPage($bunchSize);
        for ($page = 1; $page <= $maxPage; $page++) {
            /** @var SearchCriteriaInterface $filter */
            $filter = $this->searchCriteriaBuilder
                ->addFilter('state', $this->getCompleteOrderStatusList->execute(), 'nin')
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
     * @param int $bunchSize
     * @return int
     */
    private function getMaxPage(int $bunchSize): int
    {
        /** @var SearchCriteriaInterface $filter */
        $filter = $this->searchCriteriaBuilder
            ->addFilter('state', $this->getCompleteOrderStatusList->execute(), 'nin')
            ->setPageSize(1)
            ->create();

        $result = $this->orderRepository->getList($filter);
        return (int)ceil($result->getTotalCount() / $bunchSize);
    }
}
