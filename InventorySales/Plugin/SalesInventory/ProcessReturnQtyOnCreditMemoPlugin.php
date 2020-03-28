<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\SalesInventory;

use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface;
use Magento\InventorySalesApi\Model\ReturnProcessor\ProcessRefundItemsInterface;
use Magento\InventorySalesApi\Model\ReturnProcessor\Request\ItemsToRefundInterfaceFactory;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\SalesInventory\Model\Order\ReturnProcessor;

/**
 * Adapt return qty to stock for multi stock environment.
 */
class ProcessReturnQtyOnCreditMemoPlugin
{
    /**
     * @var GetSkuFromOrderItemInterface
     */
    private $getSkuFromOrderItem;

    /**
     * @var ItemsToRefundInterfaceFactory
     */
    private $itemsToRefundFactory;

    /**
     * @var ProcessRefundItemsInterface
     */
    private $processRefundItems;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @param GetSkuFromOrderItemInterface $getSkuFromOrderItem
     * @param ItemsToRefundInterfaceFactory $itemsToRefundFactory
     * @param ProcessRefundItemsInterface $processRefundItems
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     */
    public function __construct(
        GetSkuFromOrderItemInterface $getSkuFromOrderItem,
        ItemsToRefundInterfaceFactory $itemsToRefundFactory,
        ProcessRefundItemsInterface $processRefundItems,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        GetProductTypesBySkusInterface $getProductTypesBySkus
    ) {
        $this->getSkuFromOrderItem = $getSkuFromOrderItem;
        $this->itemsToRefundFactory = $itemsToRefundFactory;
        $this->processRefundItems = $processRefundItems;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
    }

    /**
     * Process return to stock for multi stock environment.
     *
     * @param ReturnProcessor $subject
     * @param callable $proceed
     * @param CreditmemoInterface $creditmemo
     * @param OrderInterface $order
     * @param array $returnToStockItems
     * @param bool $isAutoReturn
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        ReturnProcessor $subject,
        callable $proceed,
        CreditmemoInterface $creditmemo,
        OrderInterface $order,
        array $returnToStockItems = [],
        bool $isAutoReturn = false
    ): void {
        $items = [];
        foreach ($creditmemo->getItems() as $item) {
            if ($isAutoReturn || in_array($item->getOrderItemId(), $returnToStockItems)) {
                $orderItem = $item->getOrderItem();
                $itemSku = $this->getSkuFromOrderItem->execute($orderItem);

                if ($this->isValidItem($itemSku, $orderItem->getProductType())) {
                    $qty = (float)$item->getQty();
                    $processedQty = $orderItem->getQtyInvoiced() - $orderItem->getQtyRefunded() + $qty;
                    $items[$itemSku] = [
                        'qty' => ($items[$itemSku]['qty'] ?? 0) + $qty,
                        'processedQty' => ($items[$itemSku]['processedQty'] ?? 0) + (float)$processedQty,
                    ];
                }
            }
        }

        $itemsToRefund = [];
        foreach ($items as $sku => $data) {
            $itemsToRefund[] = $this->itemsToRefundFactory->create([
                'sku' => $sku,
                'qty' => $data['qty'],
                'processedQty' => $data['processedQty'],
            ]);
        }
        $this->processRefundItems->execute($order, $itemsToRefund, $returnToStockItems);
    }

    /**
     * Verify is item valid for return qty to stock.
     *
     * @param string $sku
     * @param string|null $typeId
     * @return bool
     */
    private function isValidItem(string $sku, ?string $typeId): bool
    {
        //TODO: https://github.com/magento-engcom/msi/issues/1761
        // If product type located in table sales_order_item is "grouped" replace it with "simple"
        if ($typeId === 'grouped') {
            $typeId = 'simple';
        }

        $productType = $typeId ?: $this->getProductTypesBySkus->execute(
            [$sku]
        )[$sku];

        return $this->isSourceItemManagementAllowedForProductType->execute($productType);
    }
}
