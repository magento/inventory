<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Api;

interface GetInventoryConfigurationInterface
{
    /**
     * @param string $sku
     * @param int $stockId
     * @return bool
     */
    public function isQtyDecimal(string $sku, int $stockId): bool;

    /**
     * @param string $sku
     * @param int $stockId
     * @return float
     */
    public function getMinQty(string $sku, int $stockId): float;

    /**
     * @param string $sku
     * @param int $stockId
     * @return float
     */
    public function getMinSaleQty(string $sku, int $stockId): float;

    /**
     * @param string $sku
     * @param int $stockId
     * @return float
     */
    public function getMaxSaleQty(string $sku, int $stockId): float;

    /**
     * Retrieve backorders status
     *
     * @param string $sku
     * @param int $stockId
     * @return int
     */
    public function getBackorders(string $sku, int $stockId): int;

    /**
     * Retrieve Quantity Increments data wrapper
     *
     * @param string $sku
     * @param int $stockId
     * @return float
     */
    public function getQtyIncrements(string $sku, int $stockId): float;

    /**
     * @param string $sku
     * @param int $stockId
     * @return bool
     */
    public function isEnableQtyIncrements(string $sku, int $stockId): bool;

    /**
     * @param string $sku
     * @param int $stockId
     * @return bool
     */
    public function isManageStock(string $sku, int $stockId): bool;

    /**
     * @param string $sku
     * @param int $stockId
     * @return string
     */
    public function getLowStockDate(string $sku, int $stockId): string;

    /**
     * @param string $sku
     * @param int $stockId
     * @return bool
     */
    public function isDecimalDivided(string $sku, int $stockId): bool;

    /**
     * @param string $sku
     * @param int $stockId
     * @return bool
     */
    public function isStockStatusChangedAuto(string $sku, int $stockId): bool;

    /**
     * @param string $sku
     * @param int $stockId
     * @return float
     */
    public function getStockThresholdQty(string $sku, int $stockId): float;
}
