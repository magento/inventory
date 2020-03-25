<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Plugin\InventorySalesApi\Model\GetSkuFromOrderItem;

use Magento\Bundle\Model\Product\Type;
use Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Get simple product SKU from bundle order item plugin.
 */
class AdaptGetSkuFromOrderItemPlugin
{
    /**
     * Retrieve bundle selection sku from order item.
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
        if ($orderItem->getProductType() !== Type::TYPE_CODE) {
            return $proceed($orderItem);
        }

        $orderItemOptions = $orderItem->getProductOptions();
        $value = reset($orderItemOptions['bundle_options']);
        $value = reset($value['value']);

        return $value['title'];
    }
}
