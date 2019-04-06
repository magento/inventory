<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\Order;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Check if order can be fulfilled: if its pickup location has enough QTY
 */
class IsFulfillable
{
    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilder
     */
    public function __construct(
        SourceItemRepositoryInterface $sourceItemRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilder
    ) {
        $this->sourceItemRepository = $sourceItemRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilder;
    }

    /**
     * @param OrderInterface $order
     *
     * @return bool
     */
    public function execute(OrderInterface $order): bool
    {
        if ($order->getExtensionAttributes() && $order->getExtensionAttributes()->getPickupLocationCode()) {
            $sourceCode = $order->getExtensionAttributes()->getPickupLocationCode();
            foreach ($order->getItems() as $item) {
                if (!$this->isItemFulfillable($item->getSku(), $sourceCode, (float)$item->getQtyOrdered())) {
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
     * @param float $qtyOrdered
     *
     * @return bool
     */
    private function isItemFulfillable(string $sku, string $sourceCode, float $qtyOrdered): bool
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

            return bccomp((string)$sourceItem->getQuantity(), (string)$qtyOrdered) >= 0;
        }

        return false;
    }
}
