<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Model;

use Magento\InventoryReservationCli\Model\ResourceModel\GetOrdersTotalCount;
use Magento\InventoryReservationCli\Model\SalableQuantityInconsistency\AddCompletedOrdersToForUnresolvedReservations;
use Magento\InventoryReservationCli\Model\SalableQuantityInconsistency\LoadExistingReservations;
use Magento\InventoryReservationCli\Model\SalableQuantityInconsistency\AddExpectedReservations;
use Magento\InventoryReservationCli\Model\SalableQuantityInconsistency\Collector;
use Magento\InventoryReservationCli\Model\SalableQuantityInconsistency\CollectorFactory;
use Magento\InventoryReservationCli\Model\SalableQuantityInconsistency\FilterExistingOrders;
use Magento\InventoryReservationCli\Model\SalableQuantityInconsistency\FilterManagedStockProducts;
use Magento\InventoryReservationCli\Model\SalableQuantityInconsistency\FilterUnresolvedReservations;
use Magento\InventoryReservationsApi\Model\ReservationInterface;

/**
 * Filter orders for missing initial reservation
 */
class GetSalableQuantityInconsistencies
{
    /**
     * @var CollectorFactory
     */
    private $collectorFactory;

    /**
     * @var AddExpectedReservations
     */
    private $addExpectedReservations;

    /**
     * @var LoadExistingReservations
     */
    private $loadExistingReservations;

    /**
     * @var AddCompletedOrdersToForUnresolvedReservations
     */
    private $addCompletedOrdersToUnresolved;

    /**
     * @var FilterExistingOrders
     */
    private $filterExistingOrders;

    /**
     * @var FilterUnresolvedReservations
     */
    private $filterUnresolvedReservations;

    /**
     * @var FilterManagedStockProducts
     */
    private $filterManagedStockProducts;

    /**
     * @var GetOrdersTotalCount
     */
    private $getOrdersTotalCount;

    /**
     * @var ReservationInterface[]
     */
    private $existingReservations;

    /**
     * @param CollectorFactory $collectorFactory
     * @param AddExpectedReservations $addExpectedReservations
     * @param LoadExistingReservations $loadExistingReservations
     * @param AddCompletedOrdersToForUnresolvedReservations $addCompletedOrdersToUnresolved
     * @param FilterExistingOrders $filterExistingOrders
     * @param FilterUnresolvedReservations $filterUnresolvedReservations
     * @param FilterManagedStockProducts $filterManagedStockProducts
     * @param GetOrdersTotalCount $getOrdersTotalCount
     */
    public function __construct(
        CollectorFactory $collectorFactory,
        AddExpectedReservations $addExpectedReservations,
        LoadExistingReservations $loadExistingReservations,
        AddCompletedOrdersToForUnresolvedReservations $addCompletedOrdersToUnresolved,
        FilterExistingOrders $filterExistingOrders,
        FilterUnresolvedReservations $filterUnresolvedReservations,
        FilterManagedStockProducts $filterManagedStockProducts,
        GetOrdersTotalCount $getOrdersTotalCount
    ) {
        $this->collectorFactory = $collectorFactory;
        $this->addExpectedReservations = $addExpectedReservations;
        $this->loadExistingReservations = $loadExistingReservations;
        $this->addCompletedOrdersToUnresolved = $addCompletedOrdersToUnresolved;
        $this->filterExistingOrders = $filterExistingOrders;
        $this->filterUnresolvedReservations = $filterUnresolvedReservations;
        $this->filterManagedStockProducts = $filterManagedStockProducts;
        $this->getOrdersTotalCount = $getOrdersTotalCount;
    }

    /**
     * Load filtered orders for missing initial reservation by bunch size
     *
     * The method returns inconsistencies in bunches by Generator to avoid out of memory exception
     *
     * @param int $bunchSize
     * @return \Generator
     */
    public function execute(int $bunchSize = 50): \Generator
    {
        $maxPage = $this->retrieveMaxPage($bunchSize) ?: 1;
        for ($page = 1; $page <= $maxPage; $page++) {
            $collector = $this->collectorFactory->create();
            $this->addExpectedReservations->execute($collector, $bunchSize, $page);
            $this->mergeExistingReservations($collector, $page === $maxPage);
            $this->addCompletedOrdersToUnresolved->execute($collector);
            $items = $this->filterItems($collector->getItems());
            unset($collector);

            yield $items;
        }

        $this->existingReservations = null;
    }

    /**
     * Filter list of inconsistencies
     *
     * @param array $items
     * @return array
     */
    private function filterItems(array $items): array
    {
        $items = $this->filterUnresolvedReservations->execute($items);
        $items = $this->filterManagedStockProducts->execute($items);
        $items = $this->filterUnresolvedReservations->execute($items);
        $items = $this->filterExistingOrders->execute($items);

        return $items;
    }

    /**
     * Retrieve max page for given bunch size
     *
     * @param int $bunchSize
     * @return int
     */
    private function retrieveMaxPage(int $bunchSize): int
    {
        $ordersTotalCount = $this->getOrdersTotalCount->execute();
        return (int) ceil($ordersTotalCount / $bunchSize);
    }

    /**
     * @param Collector $collector
     * @param bool $isLastPage
     */
    private function mergeExistingReservations(Collector $collector, bool $isLastPage): void
    {
        foreach ($this->getExistingReservations() as $key => $reservations) {
            /** Adds the rest of the existing reservations to the last page */
            if (isset($collector->getItems()[$key]) || $isLastPage) {
                foreach ($reservations as $reservation) {
                    $collector->addReservation($reservation);
                }
                unset($this->existingReservations[$key]);
            }
        }
    }

    /**
     * Get existing reservations
     *
     * @return ReservationInterface[]
     */
    private function getExistingReservations(): array
    {
        if ($this->existingReservations === null) {
            $this->existingReservations = $this->loadExistingReservations->execute();
        }

        return $this->existingReservations;
    }
}
