<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Model;

/**
 * Interface for configuration of inventory
 */
interface InventoryConfigurationInterface
{
    /**
     * Check if is possible subtract value from item qty
     *
     * @param int $store
     * @return bool
     */
    public function canSubtractQty($store = null): bool;

    /**
     * Get min quantity
     *
     * @param int $store
     * @return float
     */
    public function getMinQty($store = null): float;

    /**
     * Get min sale quantity
     *
     * @param int $store
     * @param int|null $customerGroupId
     * @return float
     */
    public function getMinSaleQty($store = null, ?int $customerGroupId = null): float;

    /**
     * Get max sale quantity
     *
     * @param int $store
     * @return float
     */
    public function getMaxSaleQty($store = null): float;

    /**
     * Get notify stock quantity
     *
     * @param int $store
     * @return float
     */
    public function getNotifyStockQty($store = null): float;

    /**
     * Retrieve whether Quantity Increments is enabled
     *
     * @param int $store
     * @return bool
     */
    public function isQtyIncrementsEnabled($store = null): bool;

    /**
     * Get quantity Increments
     *
     * @param int $store
     * @return float
     */
    public function getQtyIncrements($store = null): float;

    /**
     * Retrieve backorders status
     *
     * @param int $store
     * @return int
     */
    public function getBackorders($store = null): int;

    /**
     * Retrieve Manage Stock data wrapper
     *
     * @param int $store
     * @return int
     */
    public function getManageStock($store = null): int;

    /**
     * Retrieve can Back in stock
     *
     * @param int $store
     * @return bool
     */
    public function isCanBackInStock($store = null): bool;

    /**
     * Display out of stock products option
     *
     * @param int $store
     * @return bool
     */
    public function isShowOutOfStock($store = null): bool;

    /**
     * Check if credit memo items auto return option is enabled
     *
     * @param int $store
     * @return bool
     */
    public function isAutoReturnEnabled($store = null): bool;

    /**
     * Get 'Display product stock status' option value
     *
     * Shows if it is necessary to show product stock status ('in stock'/'out of stock')
     *
     * @param int $store
     * @return bool
     */
    public function isDisplayProductStockStatus($store = null): bool;
}
