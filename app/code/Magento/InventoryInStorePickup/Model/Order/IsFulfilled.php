<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\Order;

use Magento\InventoryApi\Api\Data\SourceItemInterface;

class IsFulfilled
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Magento\InventoryApi\Api\SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * IsReadyForPickup constructor.
     *
     * @param \Magento\Sales\Api\OrderRepositoryInterface             $orderRepository
     * @param \Magento\InventoryApi\Api\SourceItemRepositoryInterface $sourceItemRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilderFactory     $searchCriteriaBuilder
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\InventoryApi\Api\SourceItemRepositoryInterface $sourceItemRepository,
        \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilder
    ) {
        $this->orderRepository = $orderRepository;
        $this->sourceItemRepository = $sourceItemRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilder;
    }

    /**
     * @param int $orderId
     *
     * @return bool
     */
    public function execute(int $orderId):bool
    {
        $order = $this->orderRepository->get($orderId);

        if ($sourceCode = $order->getExtensionAttributes()->getPickupLocationCode()) {
            foreach ($order->getItems() as $item) {
                if (!$this->isItemFulfilled($item->getSku(), $sourceCode, (float)$item->getQtyOrdered())) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @param string $sku
     * @param string $sourceCode
     * @param float  $qtyOrdered
     *
     * @return bool
     */
    private function isItemFulfilled(string $sku, string $sourceCode, float $qtyOrdered):bool
    {
        $searchCriteria = $this->searchCriteriaBuilderFactory
            ->create()
            ->addFilter(SourceItemInterface::SOURCE_CODE, $sourceCode)
            ->addFilter(SourceItemInterface::SKU, $sku)
            ->create();

        $sourceItems = $this->sourceItemRepository->getList($searchCriteria);
        if ($sourceItems->getTotalCount()) {
            /** @var SourceItemInterface $sourceItem */
            $sourceItem = current($sourceItems->getItems());

            return $sourceItem->getQuantity() >= $qtyOrdered;
        }

        return false;
    }
}
