<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryExportStock\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Model\IsProductAssignedToStockInterface;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForSkuInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;

/**
 * Class Provides stock data with reservation taken into in account
 */
class PreciseExportStockProcessor
{
    /**
     * @var IsSourceItemManagementAllowedForSkuInterface
     */
    private $isSourceItemManagementAllowedForSku;

    /**
     * @var GetProductSalableQtyInterface
     */
    private $getProductSalableQty;

    /**
     * @var GetStockItemConfiguration
     */
    private $getStockItemConfiguration;

    /**
     * @var GetQtyForNotManageStock
     */
    private $getQtyForNotManageStock;

    /**
     * @var IsProductAssignedToStockInterface
     */
    private $isProductAssignedToStock;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @param IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowedForSku
     * @param GetProductSalableQtyInterface $getProductSalableQty
     * @param GetQtyForNotManageStock $getQtyForNotManageStock
     * @param GetStockItemConfiguration $getStockItemConfiguration
     * @param IsProductAssignedToStockInterface $isProductAssignedToStock
     * @param AreProductsSalableInterface $areProductsSalable
     */
    public function __construct(
        IsSourceItemManagementAllowedForSkuInterface $isSourceItemManagementAllowedForSku,
        GetProductSalableQtyInterface $getProductSalableQty,
        GetQtyForNotManageStock $getQtyForNotManageStock,
        GetStockItemConfiguration $getStockItemConfiguration,
        IsProductAssignedToStockInterface $isProductAssignedToStock,
        AreProductsSalableInterface $areProductsSalable
    ) {
        $this->isSourceItemManagementAllowedForSku = $isSourceItemManagementAllowedForSku;
        $this->getProductSalableQty = $getProductSalableQty;
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->getQtyForNotManageStock = $getQtyForNotManageStock;
        $this->isProductAssignedToStock = $isProductAssignedToStock;
        $this->areProductsSalable = $areProductsSalable;
    }

    /**
     * Provides precise method for getting stock data
     *
     * @param array $products
     * @param int $stockId
     * @return array
     * @throws LocalizedException
     */
    public function execute(array $products, int $stockId): array
    {
        $skus = $this->getProductSkus($products);
        $items = [];
        foreach ($skus as $sku) {
            try {
                $items[] = $this->getItem($sku, $stockId);
            } catch (SkuIsNotAssignedToStockException $e) {
                continue;
            }

        }

        return $items;
    }

    /**
     * Extracts product skus from $product array
     *
     * @param array $products
     * @return array
     */
    private function getProductSkus(array $products): array
    {
        $skus = [];
        /** @var ProductInterface $product */
        foreach ($products as $product) {
            $skus[] = $product->getSku();
        }

        return $skus;
    }

    /**
     * Provides is product salable, and is salable by sku
     *
     * @param string $sku
     * @param int $stockId
     * @return array
     * @throws SkuIsNotAssignedToStockException
     * @throws LocalizedException
     */
    private function getItem(string $sku, int $stockId): array
    {
        if (!$this->isSourceItemManagementAllowedForSku->execute($sku)) {
            $result = $this->areProductsSalable->execute([$sku], $stockId);
            $result = current($result);
            return [
                'sku' => $sku,
                'qty' => 0.0000,
                'is_salable' => $result->isSalable(),
            ];
        }
        if (!$this->getStockItemConfiguration->execute($sku)->isManageStock()) {
            return [
                'sku' => $sku,
                'qty' => (float)$this->getQtyForNotManageStock->execute(),
                'is_salable' => true,
            ];
        }
        if (!$this->isProductAssignedToStock->execute($sku, $stockId)) {
            throw new SkuIsNotAssignedToStockException(__('The requested sku is not assigned to given stock.'));
        }

        $result = $this->areProductsSalable->execute([$sku], $stockId);
        $result = current($result);

        return [
            'sku' => $sku,
            'qty' => $this->getProductSalableQty->execute($sku, $stockId),
            'is_salable' => $result->isSalable(),
        ];
    }
}
