<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryShipping\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterface;
use Magento\InventorySourceSelectionApi\Api\Data\ItemRequestInterfaceFactory;
use Magento\InventorySourceSelectionApi\Api\Data\InventoryRequestInterfaceFactory;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;

/**
 * Create inventory request from order
 */
class InventoryRequestFromOrderFactory
{
    /**
     * @var ItemRequestInterfaceFactory
     */
    private $itemRequestFactory;

    /**
     * @var InventoryRequestInterfaceFactory
     */
    private $inventoryRequestFactory;

    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteIdResolver;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var GetAddressRequestFromOrder
     */
    private $getAddressRequestFromOrder;

    /**
     * InventoryRequestFactory constructor.
     *
     * @param ItemRequestInterfaceFactory $itemRequestFactory
     * @param InventoryRequestInterfaceFactory $inventoryRequestFactory
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param GetAddressRequestFromOrder $getAddressRequestFromOrder
     */
    public function __construct(
        ItemRequestInterfaceFactory $itemRequestFactory,
        InventoryRequestInterfaceFactory $inventoryRequestFactory,
        StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        GetAddressRequestFromOrder $getAddressRequestFromOrder
    ) {
        $this->itemRequestFactory = $itemRequestFactory;
        $this->inventoryRequestFactory = $inventoryRequestFactory;
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->getAddressRequestFromOrder = $getAddressRequestFromOrder;
    }

    /**
     * Create inventory request from order
     *
     * @param OrderInterface $order
     * @return InventoryRequestInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function create(OrderInterface $order) : InventoryRequestInterface
    {
        $requestItems = [];
        $websiteId = $order->getStore()->getWebsiteId();
        $stockId = (int)$this->stockByWebsiteIdResolver->execute((int)$websiteId)->getStockId();

        /** @var OrderItemInterface|OrderItem $orderItem */
        foreach ($order->getItems() as $orderItem) {
            $itemSku = $orderItem->getSku() ?: $this->getSkusByProductIds->execute(
                [$orderItem->getProductId()]
            )[$orderItem->getProductId()];
            $qtyToDeliver = $orderItem->getQtyToShip();

            //check if order item is not delivered yet
            if ($orderItem->isDeleted()
                || $orderItem->getParentItemId()
                || $this->isZero((float)$qtyToDeliver)
                || $orderItem->getIsVirtual()
            ) {
                continue;
            }

            $requestItems[] = $this->itemRequestFactory->create([
                    'sku' => $itemSku,
                    'qty' => $qtyToDeliver
            ]);
        }

        return $this->inventoryRequestFactory->create([
            'stockId' => $stockId,
            'items' => $requestItems,
            'address' => $this->getAddressRequestFromOrder->execute($order)
        ]);
    }

    /**
     * Compare float number with some epsilon
     *
     * @param float $floatNumber
     *
     * @return bool
     */
    private function isZero(float $floatNumber): bool
    {
        return $floatNumber < 0.0000001;
    }
}
