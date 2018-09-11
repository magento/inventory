<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Model;

use Magento\InventorySalesAdminUi\Model\ResourceModel\GetAssignedStockIdsBySku;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;

/**
 * Get salable quantity data by sku
 */
class GetSalableQuantityDataBySku
{
    /**
     * @var GetProductSalableQtyInterface
     */
    private $getProductSalableQty;

    /**
     * @var StockRepositoryInterface
     */
    private $stockRepository;

    /**
     * @var GetAssignedStockIdsBySku
     */
    private $getAssignedStockIdsBySku;

    /**
     * @var GetStockConfigurationInterface
     */
    private $getStockConfiguration;

    /**
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param StockRepositoryInterface $stockRepository
     * @param GetAssignedStockIdsBySku $getAssignedStockIdsBySku
     * @param GetStockConfigurationInterface $getStockConfiguration
     */
    public function __construct(
        GetProductSalableQtyInterface $getProductSalableQty,
        StockRepositoryInterface $stockRepository,
        GetAssignedStockIdsBySku $getAssignedStockIdsBySku,
        GetStockConfigurationInterface $getStockConfiguration
    ) {
        $this->getProductSalableQty = $getProductSalableQty;
        $this->stockRepository = $stockRepository;
        $this->getAssignedStockIdsBySku = $getAssignedStockIdsBySku;
        $this->getStockConfiguration = $getStockConfiguration;
    }

    /**
     * @param string $sku
     * @return array
     */
    public function execute(string $sku): array
    {
        $stockInfo = [];
        $stockIds = $this->getAssignedStockIdsBySku->execute($sku);
        if (count($stockIds)) {
            foreach ($stockIds as $stockId) {
                $stockId = (int)$stockId;
                $stock = $this->stockRepository->get($stockId);
                $isManageStock = $this->getManageStock($sku, $stockId);
                $stockInfo[] = [
                    'stock_name' => $stock->getName(),
                    'qty' => $isManageStock ? $this->getProductSalableQty->execute($sku, $stockId) : null,
                    'manage_stock' => $isManageStock,
                ];
            }
        }
        return $stockInfo;
    }

    private function getManageStock(string $sku, int $stockId)
    {
        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId);
        $stockConfiguration = $this->getStockConfiguration->forStock($stockId);
        $globalConfiguration = $this->getStockConfiguration->forGlobal();

        $defaultValue = $stockConfiguration->isManageStock() !== null
            ? $stockConfiguration->isManageStock()
            : $globalConfiguration->isManageStock();

        return $stockItemConfiguration->isManageStock() !== null
            ? $stockItemConfiguration->isManageStock()
            : $defaultValue;
    }
}
