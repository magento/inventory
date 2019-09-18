<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogFrontendUi\Model;

use Magento\InventoryCatalogFrontendUi\Model\QtyLeftChecker;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;

/**
 * Model for getting product qty left.
 */
class GetProductQtyLeft
{
    /**
     * @var QtyLeftChecker
     */
    private $qtyLeftChecker;

    /**
     * @var GetProductSalableQtyInterface
     */
    private $getProductSalableQty;

    /**
     * @param QtyLeftChecker $qtyLeftChecker
     * @param GetProductSalableQtyInterface $getProductSalableQty
     */
    public function __construct(
        QtyLeftChecker $qtyLeftChecker,
        GetProductSalableQtyInterface $getProductSalableQty
    ) {
        $this->qtyLeftChecker = $qtyLeftChecker;
        $this->getProductSalableQty = $getProductSalableQty;
    }

    /**
     * Gte product qty info.
     *
     * @param string $productSku
     * @param int $stockId
     * @return float|null
     */
    public function execute(string $productSku, int $stockId):? float
    {
        $productSalableQty = $this->getProductSalableQty->execute($productSku, $stockId);
        if ($this->qtyLeftChecker->useQtyForViewing($productSalableQty)) {
            return  $productSalableQty;
        }

        return null;
    }
}
