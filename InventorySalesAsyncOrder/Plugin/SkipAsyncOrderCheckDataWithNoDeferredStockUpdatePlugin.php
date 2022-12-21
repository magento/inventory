<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAsyncOrder\Plugin;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventorySales\Model\ReservationExecutionInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Skip checking data if it is processing of async order with no deferred stock update.
 */
class SkipAsyncOrderCheckDataWithNoDeferredStockUpdatePlugin
{
    /**
     * @var ReservationExecutionInterface
     */
    private $reservationExecution;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param ReservationExecutionInterface $reservationExecution
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ReservationExecutionInterface $reservationExecution,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->reservationExecution = $reservationExecution;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Skip checking data if it is processing of async order with "received" status and no deferred stock update.
     *
     * @param AbstractItem $subject
     * @param \Closure $proceed
     * @return void
     */
    public function aroundCheckData(
        AbstractItem $subject,
        \Closure     $proceed
    ) {
        if ($this->reservationExecution->isDeferred()) {
            return $proceed();
        } else {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('quote_id', $subject->getQuoteId())
                ->addFilter('status', 'received')
                ->create();
            $asyncOrder = $this->orderRepository->getList($searchCriteria)->getItems();

            if (!$asyncOrder) {
                return $proceed();
            }
        }
    }
}
