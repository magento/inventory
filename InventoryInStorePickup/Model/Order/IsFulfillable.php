<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\Order;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
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
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var GetPickupLocationCode
     */
    private $getPickupLocationCode;

    /**
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param SearchCriteriaBuilderFactory $searchCriteriaBuilder
     * @param SourceRepositoryInterface $sourceRepository
     * @param GetPickupLocationCode $getPickupLocationCode
     */
    public function __construct(
        SourceItemRepositoryInterface $sourceItemRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilder,
        SourceRepositoryInterface $sourceRepository,
        GetPickupLocationCode $getPickupLocationCode
    ) {
        $this->sourceItemRepository = $sourceItemRepository;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilder;
        $this->sourceRepository = $sourceRepository;
        $this->getPickupLocationCode = $getPickupLocationCode;
    }

    /**
     * Check if items are ordered form the Pickup location and verify that each item has enough quantity.
     *
     * @param OrderInterface $order
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function execute(OrderInterface $order): bool
    {
        $sourceCode = $this->getPickupLocationCode->execute($order);

        if ($sourceCode) {
            foreach ($order->getItems() as $item) {
                if (!$this->isItemFulfillable($item->getSku(), $sourceCode, (float)$item->getQtyOrdered())) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check if Pickup Location source has enough item qty.
     *
     * @param string $sku
     * @param string $sourceCode
     * @param float $qtyOrdered
     *
     * @return bool
     * @throws NoSuchEntityException
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
            $source = $this->sourceRepository->get($sourceCode);

            return bccomp((string)$sourceItem->getQuantity(), (string)$qtyOrdered, 4) >= 0 &&
                $sourceItem->getStatus() === SourceItemInterface::STATUS_IN_STOCK &&
                $source->isEnabled();
        }

        return false;
    }
}
