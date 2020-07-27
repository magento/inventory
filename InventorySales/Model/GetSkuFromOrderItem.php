<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\Catalog\Model\Product\Type;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * @inheritDoc
 */
class GetSkuFromOrderItem implements GetSkuFromOrderItemInterface
{
    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     */
    public function __construct(
        GetSkusByProductIdsInterface $getSkusByProductIds,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
    ) {
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
    }

    /**
     * @inheritdoc
     */
    public function execute(OrderItemInterface $orderItem): string
    {
        $itemSku = $orderItem->getSku();
        if ($orderItem->getProductType() === Type::TYPE_BUNDLE) {
            $orderItemOptions = $orderItem->getProductOptions();
            $value = reset($orderItemOptions['bundle_options']);
            $value = reset($value['value']);
            $itemSku = $value['title'];
        }
        try {
            if ($this->isSourceItemManagementAllowedForProductType->execute($orderItem->getProductType())) {
                $itemSku = $this->getSkusByProductIds->execute(
                    [$orderItem->getProductId()]
                )[$orderItem->getProductId()];
            }
        } catch (NoSuchEntityException $e) {
            $itemSku = $orderItem->getSku();
        }

        return $itemSku;
    }
}
