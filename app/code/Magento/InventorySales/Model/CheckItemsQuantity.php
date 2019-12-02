<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySalesApi\Api\AreProductsSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterface;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;

/**
 * Verify items are salable for requested quantity.
 */
class CheckItemsQuantity
{
    /**
     * @deprecated
     * @see AreProductsSalableForRequestedQty
     * @var IsProductSalableForRequestedQtyInterface
     */
    private $isProductSalableForRequestedQty;

    /**
     * @var AreProductsSalableForRequestedQtyInterface
     */
    private $areProductsSalableForRequestedQty;

    /**
     * @param IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty
     * @param AreProductsSalableForRequestedQtyInterface $areProductsSalableForRequestedQty
     */
    public function __construct(
        IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty,
        AreProductsSalableForRequestedQtyInterface $areProductsSalableForRequestedQty
    ) {
        $this->isProductSalableForRequestedQty = $isProductSalableForRequestedQty;
        $this->areProductsSalableForRequestedQty = $areProductsSalableForRequestedQty;
    }

    /**
     * Check whether all items salable
     *
     * @param array $items ['sku' => 'qty', ...]
     * @param int $stockId
     * @return void
     * @throws LocalizedException
     */
    public function execute(array $items, int $stockId): void
    {
        $result = $this->areProductsSalableForRequestedQty->execute($items, $stockId);
        foreach ($result as $isSalable) {
            if (false === $isSalable->isSalable()) {
                $errors = $isSalable->getErrors();
                /** @var ProductSalabilityErrorInterface $errorMessage */
                $errorMessage = array_pop($errors);
                throw new LocalizedException(__($errorMessage->getMessage()));
            }
        }
    }
}
