<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\Catalog\Model;

use Magento\CatalogInventory\Model\ProductQty;
use Magento\InventoryCatalog\Model\GetProductQtyById;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryCatalog\Model\QtyLeftChecker;

/**
 * Plugin for adapt qty left for product according to the stock.
 */
class ProductQtyPlugin
{
    /**
     * @var StockItemConfigurationInterface
     */
    private $stockItemConfig;

    /**
     * @var GetProductQtyById
     */
    private $getProductQtyById;

    /**
     * @var QtyLeftChecker
     */
    private $qtyLeftChecker;

    /**
     * @param StockItemConfigurationInterface $stockItemConfiguration
     * @param GetProductQtyById $getProductQtyById
     * @param QtyLeftChecker $qtyLeftChecker
     */
    public function __construct(
        StockItemConfigurationInterface $stockItemConfiguration,
        GetProductQtyById $getProductQtyById,
        QtyLeftChecker $qtyLeftChecker
    ) {
        $this->stockItemConfig = $stockItemConfiguration;
        $this->getProductQtyById = $getProductQtyById;
        $this->qtyLeftChecker = $qtyLeftChecker;
    }

    /**
     * Get product qty left.
     *
     * @param ProductQty $subject
     * @param callable $proceed
     * @param int $productId
     * @param int $websiteId
     * @return float|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetProductQtyLeft(
        ProductQty $subject,
        callable $proceed,
        int $productId,
        int $websiteId
    ):? float {
        $productSalableQty = $this->getProductQtyById->execute($productId);
        if ($this->qtyLeftChecker->useQtyForViewing($productSalableQty)) {
            return  $this->getStockQtyLeft($productSalableQty);
        }

        return null;
    }

    /**
     * Get stock qty left.
     *
     * @param float $productSalableQty
     * @return float
     */
    private function getStockQtyLeft(float $productSalableQty): float
    {
        return (float)($productSalableQty - $this->stockItemConfig->getMinQty());
    }
}
