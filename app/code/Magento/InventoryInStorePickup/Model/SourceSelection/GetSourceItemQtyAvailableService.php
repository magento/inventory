<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\SourceSelection;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryInStorePickup\Model\Source\GetIsPickupLocationActive;
use Magento\InventorySourceSelectionApi\Model\GetSourceItemQtyAvailableInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Get source item qty available for usage in SSA
 * InStore-Pickup implementation that decrements qty in case it is reserved by
 * active store-pickup orders
 * @See https://github.com/magento-engcom/msi/wiki/Support-of-Store-Pickup-for-Multi-Source-Inventory#Race-condition-between-orders
 */
class GetSourceItemQtyAvailableService implements GetSourceItemQtyAvailableInterface
{
    /**
     * @var GetActiveStorePickupOrdersBySource
     */
    private $getSourceActiveStorePickupOrders;

    /**
     * @var GetOrderItemsByOrdersListAndSku
     */
    private $getOrderItemsByOrdersListAndSku;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var GetIsPickupLocationActive
     */
    private $getIsPickupLocationActive;

    /**
     * @param GetActiveStorePickupOrdersBySource $getActiveStorePickupOrders
     * @param GetOrderItemsByOrdersListAndSku $getOrderItemsByOrdersListAndSku
     * @param SourceRepositoryInterface $sourceRepository
     * @param GetIsPickupLocationActive $getIsPickupLocationActive
     */
    public function __construct(
        GetActiveStorePickupOrdersBySource $getActiveStorePickupOrders,
        GetOrderItemsByOrdersListAndSku $getOrderItemsByOrdersListAndSku,
        SourceRepositoryInterface $sourceRepository,
        GetIsPickupLocationActive $getIsPickupLocationActive
    ) {
        $this->getSourceActiveStorePickupOrders = $getActiveStorePickupOrders;
        $this->getOrderItemsByOrdersListAndSku = $getOrderItemsByOrdersListAndSku;
        $this->sourceRepository = $sourceRepository;
        $this->getIsPickupLocationActive = $getIsPickupLocationActive;
    }

    /**
     * @inheritDoc
     * @throws NoSuchEntityException
     */
    public function execute(SourceItemInterface $sourceItem): float
    {
        /* TODO: create config and check if store pickup is enabled? */
        return $sourceItem->getQuantity() - $this->getStorePickupReservedQty($sourceItem);
    }

    /**
     * @param SourceItemInterface $sourceItem
     *
     * @return float
     * @throws NoSuchEntityException
     */
    private function getStorePickupReservedQty(SourceItemInterface $sourceItem): float
    {
        $storePickupOrders = $this->getStorePickupOrdersBySourceItem($sourceItem);
        if (!$storePickupOrders) {
            return 0.0;
        }

        $orderItems = $this->getOrderItemsByOrdersListAndSku->execute($storePickupOrders, $sourceItem->getSku());

        return array_reduce(
            $orderItems->getItems(),
            function (float $sum, OrderItemInterface $item) {
                return $sum + $item->getQtyOrdered();
            },
            0.0
        );
    }

    /**
     * @param SourceItemInterface $sourceItem
     *
     * @return OrderInterface[]
     * @throws NoSuchEntityException
     */
    private function getStorePickupOrdersBySourceItem(SourceItemInterface $sourceItem): array
    {
        $source = $this->sourceRepository->get($sourceItem->getSourceCode());

        if ($this->getIsPickupLocationActive->execute($source)) {
            return $this->getSourceActiveStorePickupOrders
                ->execute($source->getSourceCode())
                ->getItems();
        }

        return [];
    }
}
