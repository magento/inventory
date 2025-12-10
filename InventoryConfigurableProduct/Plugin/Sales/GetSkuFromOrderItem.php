<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\Sales;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Get simple product SKU from configurable order item
 */
class GetSkuFromOrderItem
{
    /**
     * Gets the SKU of a simple product from a configurable order item, or proceeds with the default behavior.
     *
     * @param GetSkuFromOrderItemInterface $subject
     * @param callable $proceed
     * @param OrderItemInterface $orderItem
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        GetSkuFromOrderItemInterface $subject,
        callable $proceed,
        OrderItemInterface $orderItem
    ): string {
        if ($orderItem->getProductType() !== Configurable::TYPE_CODE) {
            return $proceed($orderItem);
        }

        $orderItemOptions = $orderItem->getProductOptions();
        $sku = $orderItemOptions['simple_sku'];

        return $sku;
    }
}
