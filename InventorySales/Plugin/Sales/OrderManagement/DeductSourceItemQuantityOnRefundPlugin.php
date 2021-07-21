<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\Sales\OrderManagement;

use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySales\Model\ReturnProcessor\DeductSourceItemQuantityOnRefund;
use Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface;
use Magento\InventorySalesApi\Model\ReturnProcessor\Request\ItemsToRefundInterfaceFactory;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface as CreditmemoItem;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class DeductSourceItemQuantityOnRefundPlugin
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
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @var DeductSourceItemQuantityOnRefund
     */
    private $deductSourceItemQuantityOnRefund;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @param GetSkuFromOrderItemInterface $getSkuFromOrderItem
     * @param ItemsToRefundInterfaceFactory $itemsToRefundFactory
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     * @param DeductSourceItemQuantityOnRefund $deductSourceItemQuantityOnRefund
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        GetSkuFromOrderItemInterface $getSkuFromOrderItem,
        ItemsToRefundInterfaceFactory $itemsToRefundFactory,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        GetProductTypesBySkusInterface $getProductTypesBySkus,
        DeductSourceItemQuantityOnRefund $deductSourceItemQuantityOnRefund,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->getSkuFromOrderItem = $getSkuFromOrderItem;
        $this->itemsToRefundFactory = $itemsToRefundFactory;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
        $this->deductSourceItemQuantityOnRefund = $deductSourceItemQuantityOnRefund;
        $this->orderRepository = $orderRepository;
    }

    /**
     * On Credit Memo create, issues the reservation compensation record.
     *
     * Before saving the credit memo, validates if the credit memo object was created or updated.
     * If the credit memo was updates, we should not compensate the reservation.
     *
     * @param CreditmemoRepositoryInterface $subject
     * @param callable $proceed
     * @param CreditmemoInterface $entity
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSave(
        CreditmemoRepositoryInterface $subject,
        callable $proceed,
        CreditmemoInterface $entity
    )
    {
        $isNewCreditMemo = !(bool)$entity->getEntityId();
        $result = $proceed($entity);

        if ($isNewCreditMemo) {
            $this->compensateReservation($entity);
        }

        return $result;
    }

    /**
     * @param CreditmemoInterface $creditMemo
     * @return void
     */
    private function compensateReservation(CreditmemoInterface $creditMemo): void
    {
        $order = $this->orderRepository->get($creditMemo->getOrderId());
        $itemsToRefund = $refundedOrderItemIds = [];
        /** @var CreditmemoItem $item */
        foreach ($creditMemo->getItems() as $item) {
            /** @var OrderItemInterface $orderItem */
            $orderItem = $item->getOrderItem();
            $sku = $this->getSkuFromOrderItem->execute($orderItem);

            if ($this->isValidItem($sku, $item)) {
                $refundedOrderItemIds[] = $item->getOrderItemId();
                $qty = (float)$item->getQty();
                $processedQty = $orderItem->getQtyInvoiced() - $orderItem->getQtyRefunded() + $qty;
                $itemsToRefund[$sku] = [
                    'qty' => ($itemsToRefund[$sku]['qty'] ?? 0) + $qty,
                    'processedQty' => ($itemsToRefund[$sku]['processedQty'] ?? 0) + (float)$processedQty
                ];
            }
        }

        $itemsToDeductFromSource = [];
        foreach ($itemsToRefund as $sku => $data) {
            $itemsToDeductFromSource[] = $this->itemsToRefundFactory->create([
                'sku' => $sku,
                'qty' => $data['qty'],
                'processedQty' => $data['processedQty']
            ]);
        }

        if (!empty($itemsToDeductFromSource)) {
            $this->deductSourceItemQuantityOnRefund->execute(
                $order,
                $itemsToDeductFromSource,
                $refundedOrderItemIds
            );
        }
    }

    /**
     * @param string $sku
     * @param CreditmemoItem $item
     * @return bool
     */
    private function isValidItem(string $sku, CreditmemoItem $item): bool
    {
        /** @var OrderItemInterface $orderItem */
        $orderItem = $item->getOrderItem();
        // Since simple products which are the part of a grouped product are saved in the database
        // (table sales_order_item) with product type grouped, we manually change the type of
        // product from grouped to simple which support source management.
        $typeId = $orderItem->getProductType() === 'grouped' ? 'simple' : $orderItem->getProductType();

        $productType = $typeId ?: $this->getProductTypesBySkus->execute(
            [$sku]
        )[$sku];

        return $this->isSourceItemManagementAllowedForProductType->execute($productType)
                && $item->getQty() > 0
                && !$item->getBackToStock();
    }
}
