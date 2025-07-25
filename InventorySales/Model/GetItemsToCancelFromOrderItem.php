<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\InventorySalesApi\Api\Data\ItemToSellInterface;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface;
use Magento\InventorySalesApi\Api\Data\ItemToSellInterfaceFactory;
use Magento\Framework\Serialize\Serializer\Json;

class GetItemsToCancelFromOrderItem
{
    /**
     * @var GetSkuFromOrderItemInterface
     */
    private $getSkuFromOrderItem;

    /**
     * @var ItemToSellInterfaceFactory
     */
    private $itemsToSellFactory;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @param GetSkuFromOrderItemInterface $getSkuFromOrderItem
     * @param ItemToSellInterfaceFactory $itemsToSellFactory
     * @param Json $jsonSerializer
     */
    public function __construct(
        GetSkuFromOrderItemInterface $getSkuFromOrderItem,
        ItemToSellInterfaceFactory $itemsToSellFactory,
        Json $jsonSerializer
    ) {
        $this->getSkuFromOrderItem = $getSkuFromOrderItem;
        $this->itemsToSellFactory = $itemsToSellFactory;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * Processes an order item to determine items to cancel, handling child items and grouping them by SKU and quantity.
     *
     * @param OrderItem $orderItem
     * @return ItemToSellInterface[]
     */
    public function execute(OrderItem $orderItem): array
    {
        $itemsToCancel = [];
        if ($orderItem->getHasChildren()) {
            if (!$orderItem->isDummy(true)) {
                foreach ($this->processComplexItem($orderItem) as $item) {
                    $itemsToCancel[] = $item;
                }
            }
        } elseif (!$orderItem->isDummy(true)) {
            $itemSku = $this->getSkuFromOrderItem->execute($orderItem);
            $itemsToCancel[] = $this->itemsToSellFactory->create([
                'sku' => $itemSku,
                'qty' => $this->getQtyToCancel($orderItem)
            ]);
        }

        return $this->groupItemsBySku($itemsToCancel);
    }

    /**
     * Determines items to cancel from an order, handling child items, grouping by SKU, and calculating quantities.
     *
     * @param ItemToSellInterface[] $itemsToCancel
     * @return ItemToSellInterface[]
     */
    private function groupItemsBySku(array $itemsToCancel): array
    {
        $processingItems = $groupedItems = [];
        foreach ($itemsToCancel as $item) {
            if ($item->getQuantity() == 0) {
                continue;
            }
            if (empty($processingItems[$item->getSku()])) {
                $processingItems[$item->getSku()] = $item->getQuantity();
            } else {
                $processingItems[$item->getSku()] += $item->getQuantity();
            }
        }

        foreach ($processingItems as $sku => $qty) {
            $groupedItems[] = $this->itemsToSellFactory->create([
                'sku' => $sku,
                'qty' => $qty
            ]);
        }

        return $groupedItems;
    }

    /**
     * Determines items to cancel from an order, handling complex products and grouping items by SKU and quantity.
     *
     * @param OrderItem $orderItem
     * @return ItemToSellInterface[]
     */
    private function processComplexItem(OrderItem $orderItem): array
    {
        $itemsToCancel = [];
        foreach ($orderItem->getChildrenItems() as $item) {
            $productOptions = $item->getProductOptions();
            if (isset($productOptions['bundle_selection_attributes'])) {
                $bundleSelectionAttributes = $this->jsonSerializer->unserialize(
                    $productOptions['bundle_selection_attributes']
                );
                if ($bundleSelectionAttributes) {
                    $shippedQty = $bundleSelectionAttributes['qty'] * $orderItem->getQtyShipped();
                    $qty = $item->getQtyOrdered() - max($shippedQty, $item->getQtyInvoiced()) - $item->getQtyCanceled();
                    $itemSku = $this->getSkuFromOrderItem->execute($item);
                    $itemsToCancel[] = $this->itemsToSellFactory->create([
                        'sku' => $itemSku,
                        'qty' => $qty
                    ]);
                }
            } else {
                // configurable product
                $itemSku = $this->getSkuFromOrderItem->execute($orderItem);
                $itemsToCancel[] = $this->itemsToSellFactory->create([
                    'sku' => $itemSku,
                    'qty' => $this->getQtyToCancel($orderItem)
                ]);
            }
        }
        return $itemsToCancel;
    }

    /**
     * Processes order items to determine quantities to cancel, grouping items by SKU and handling complex products.
     *
     * @param OrderItem $item
     * @return float
     */
    private function getQtyToCancel(OrderItem $item): float
    {
        return $item->getQtyOrdered() - max($item->getQtyShipped(), $item->getQtyInvoiced()) - $item->getQtyCanceled();
    }
}
