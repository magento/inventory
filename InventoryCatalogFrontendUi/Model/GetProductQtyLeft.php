<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogFrontendUi\Model;

use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;

/**
 * Model for getting product qty left.
 */
class GetProductQtyLeft
{
    /**
     * @var IsSalableQtyAvailableForDisplaying
     */
    private $qtyLeftChecker;

    /**
     * @var GetProductSalableQtyInterface
     */
    private $getProductSalableQty;

    /**
     * @param IsSalableQtyAvailableForDisplaying $qtyLeftChecker
     * @param GetProductSalableQtyInterface $getProductSalableQty
     */
    public function __construct(
        IsSalableQtyAvailableForDisplaying $qtyLeftChecker,
        GetProductSalableQtyInterface $getProductSalableQty
    ) {
        $this->qtyLeftChecker = $qtyLeftChecker;
        $this->getProductSalableQty = $getProductSalableQty;
    }

    /**
     * Get salable qty if it is possible.
     *
     * @param string $productSku
     * @param int $stockId
     * @return float
     */
    public function execute(string $productSku, int $stockId): float
    {
        $productSalableQty = $this->getProductSalableQty->execute($productSku, $stockId);
        if ($this->qtyLeftChecker->execute((float)$productSalableQty)) {
            return $productSalableQty;
        }

        return 0.0;
    }
}
