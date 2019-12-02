<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\InventorySalesApi\Api\AreProductsSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;

/**
 * @inheritDoc
 */
class AreProductsSalableForRequestedQty implements AreProductsSalableForRequestedQtyInterface
{
    /**
     * @var IsProductSalableForRequestedQtyInterface
     */
    private $isProductSalableForRequestedQtyInterface;

    /**
     * @param IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQtyInterface
     */
    public function __construct(IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQtyInterface)
    {
        $this->isProductSalableForRequestedQtyInterface = $isProductSalableForRequestedQtyInterface;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $skuRequests, int $stockId): array
    {
        $result = [];
        foreach ($skuRequests as $sku => $quantity) {
            $result[] = $this->isProductSalableForRequestedQtyInterface->execute(
                (string)$sku,
                $stockId,
                (float)$quantity
            );
        }

        return $result;
    }
}
