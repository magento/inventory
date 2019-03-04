<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryShipping\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\Shipment\Item;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface;
use Magento\InventorySourceDeductionApi\Model\ItemToDeductInterface;
use Magento\InventorySourceDeductionApi\Model\ItemToDeductInterfaceFactory;

class GetItemsToDeductFromShipment
{
    /**
     * @var GetSkuFromOrderItemInterface
     */
    private $getSkuFromOrderItem;

    /**
     * @var Json
     */
    private $jsonSerializer;

    /**
     * @var ItemToDeductInterfaceFactory
     */
    private $itemToDeduct;

    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param GetSkuFromOrderItemInterface $getSkuFromOrderItem
     * @param Json $jsonSerializer
     * @param ItemToDeductInterfaceFactory $itemToDeduct
     * @param OrderItemRepositoryInterface|null $orderItemRepository
     * @param SearchCriteriaBuilder|null $searchCriteriaBuilder
     */
    public function __construct(
        GetSkuFromOrderItemInterface $getSkuFromOrderItem,
        Json $jsonSerializer,
        ItemToDeductInterfaceFactory $itemToDeduct,
        OrderItemRepositoryInterface $orderItemRepository = null,
        SearchCriteriaBuilder $searchCriteriaBuilder = null
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->itemToDeduct = $itemToDeduct;
        $this->getSkuFromOrderItem = $getSkuFromOrderItem;
        $this->orderItemRepository = $orderItemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;

        $this->orderItemRepository = $orderItemRepository ?:
            ObjectManager::getInstance()->get(OrderItemRepositoryInterface::class);
        $this->searchCriteriaBuilder = $searchCriteriaBuilder ?:
            ObjectManager::getInstance()->get(SearchCriteriaBuilder::class);
    }

    /**
     * @param ShipmentInterface $shipment
     *
     * @return ItemToDeductInterface[]
     */
    public function execute(ShipmentInterface $shipment): array
    {
        $itemsToShip = [];

        $byOrderId = $this->searchCriteriaBuilder->addFilter('order_id', $shipment->getOrderId())->create();
        $orderItems = $this->orderItemRepository->getList($byOrderId)->getItems();

        /** @var \Magento\Sales\Model\Order\Shipment\Item $shipmentItem */
        foreach ($shipment->getAllItems() as $shipmentItem) {
            if (!$shipmentItem->getOrderItemId() || !isset($orderItems[$shipmentItem->getOrderItemId()])) {
                continue;
            }
            $orderItem = $orderItems[$shipmentItem->getOrderItemId()];
            if ($orderItem->getHasChildren()) {
                if (!$orderItem->isDummy(true)) {
                    foreach ($this->processComplexItem($shipmentItem, $orderItem) as $item) {
                        $itemsToShip[] = $item;
                    }
                }
            } else {
                $itemSku = $this->getSkuFromOrderItem->execute($orderItem);
                $qty = $this->castQty($orderItem, $shipmentItem->getQty());
                $itemsToShip[] = $this->itemToDeduct->create([
                    'sku' => $itemSku,
                    'qty' => $qty
                ]);
            }
        }

        return $this->groupItemsBySku($itemsToShip);
    }

    /**
     * @param array $shipmentItems
     * @return array
     */
    private function groupItemsBySku(array $shipmentItems): array
    {
        $processingItems = $groupedItems = [];
        foreach ($shipmentItems as $shipmentItem) {
            if (empty($processingItems[$shipmentItem->getSku()])) {
                $processingItems[$shipmentItem->getSku()] = $shipmentItem->getQty();
            } else {
                $processingItems[$shipmentItem->getSku()] += $shipmentItem->getQty();
            }
        }

        foreach ($processingItems as $sku => $qty) {
            $groupedItems[] = $this->itemToDeduct->create([
                'sku' => $sku,
                'qty' => $qty
            ]);
        }

        return $groupedItems;
    }

    /**
     * @param Item               $shipmentItem
     * @param OrderItemInterface $orderItem
     *
     * @return array
     */
    private function processComplexItem(Item $shipmentItem, OrderItemInterface $orderItem): array
    {
        $itemsToShip = [];
        /** @var OrderItem $item */
        foreach ($orderItem->getChildrenItems() as $item) {
            if ($item->getIsVirtual() || $item->getLockedDoShip()) {
                continue;
            }
            $productOptions = $item->getProductOptions();
            if (isset($productOptions['bundle_selection_attributes'])) {
                $bundleSelectionAttributes = $this->jsonSerializer->unserialize(
                    $productOptions['bundle_selection_attributes']
                );
                if ($bundleSelectionAttributes) {
                    $qty = $bundleSelectionAttributes['qty'] * $shipmentItem->getQty();
                    $qty = $this->castQty($item, $qty);
                    $itemSku = $this->getSkuFromOrderItem->execute($item);
                    $itemsToShip[] = $this->itemToDeduct->create([
                        'sku' => $itemSku,
                        'qty' => $qty
                    ]);
                    continue;
                }
            } else {
                // configurable product
                $itemSku = $this->getSkuFromOrderItem->execute($orderItem);
                $qty = $this->castQty($orderItem, $shipmentItem->getQty());
                $itemsToShip[] = $this->itemToDeduct->create([
                    'sku' => $itemSku,
                    'qty' => $qty
                ]);
            }
        }

        return $itemsToShip;
    }

    /**
     * @param OrderItem $item
     * @param string|int|float $qty
     * @return float|int
     */
    private function castQty(OrderItem $item, $qty)
    {
        if ($item->getIsQtyDecimal()) {
            $qty = (double)$qty;
        } else {
            $qty = (int)$qty;
        }

        return $qty > 0 ? $qty : 0;
    }
}
