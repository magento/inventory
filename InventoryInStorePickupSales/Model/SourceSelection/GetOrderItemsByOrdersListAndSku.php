<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickupSales\Model\SourceSelection;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\OrderItemSearchResultInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;

/**
 * Get order items by the orders list provided and a certain order item`s SKU.
 *
 * Something kind of emulation of SQL JOIN
 */
class GetOrderItemsByOrdersListAndSku
{
    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        OrderItemRepositoryInterface $orderItemRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->orderItemRepository = $orderItemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Get order items by the orders list provided and a certain order item`s SKU.
     *
     * @param array $orders
     * @param string $sku
     * @return OrderItemSearchResultInterface
     */
    public function execute(array $orders, string $sku): OrderItemSearchResultInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(
                OrderItemInterface::ORDER_ID,
                implode(
                    ',',
                    array_map(
                        function (array $row) {
                            return $row['entity_id'];
                        },
                        $orders
                    )
                ),
                'in'
            )->addFilter(OrderItemInterface::SKU, $sku)
            ->create();

        return $this->orderItemRepository->getList($searchCriteria);
    }
}
