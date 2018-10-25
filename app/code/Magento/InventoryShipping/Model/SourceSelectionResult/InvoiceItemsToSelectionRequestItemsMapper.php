<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryShipping\Model\SourceSelectionResult;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\InvoiceItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\InventorySourceSelectionApi\Api\Data\ItemRequestInterfaceFactory;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Traversable;

/**
 * Map Invoice Items to array of Request Items
 */
class InvoiceItemsToSelectionRequestItemsMapper
{
    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var ItemRequestInterfaceFactory
     */
    private $itemRequestFactory;

    /**
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param ItemRequestInterfaceFactory $itemRequestFactory
     */
    public function __construct(
        GetSkusByProductIdsInterface $getSkusByProductIds,
        ItemRequestInterfaceFactory $itemRequestFactory
    ) {
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->itemRequestFactory = $itemRequestFactory;
    }

    /**
     * @param InvoiceItemInterface[]|Traversable $invoiceItems
     * @return array
     * @throws NoSuchEntityException
     */
    public function map(Traversable $invoiceItems): array
    {
        $selectionRequestItems = [];
        foreach ($invoiceItems as $invoiceItem) {
            $orderItem = $invoiceItem->getOrderItem();

            if ($orderItem->isDummy() || !$orderItem->getIsVirtual()) {
                continue;
            }

            $itemSku = $invoiceItem->getSku() ?: $this->getSkusByProductIds->execute(
                [$invoiceItem->getProductId()]
            )[$invoiceItem->getProductId()];
            $qty = $this->castQty($invoiceItem->getOrderItem(), $invoiceItem->getQty());

            $selectionRequestItems[] = $this->itemRequestFactory->create([
                'sku' => $itemSku,
                'qty' => $qty,
            ]);
        }
        return $selectionRequestItems;
    }

    /**
     * @param OrderItemInterface $item
     * @param string|int|float $qty
     * @return float|int
     */
    private function castQty(OrderItemInterface $item, $qty)
    {
        if ($item->getIsQtyDecimal()) {
            $qty = (double)$qty;
        } else {
            $qty = (int)$qty;
        }

        return $qty > 0 ? $qty : 0;
    }
}
