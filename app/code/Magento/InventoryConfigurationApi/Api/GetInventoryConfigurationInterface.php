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
     * @return bool
     */
    public function isQtyDecimal(): bool;

    /**
     * @return bool
     */
    public function isShowDefaultNotificationMessage(): bool;

    /**
     * @return float
     */
    public function getMinQty(): float;

    /**
     * @return float
     */
    public function getMinSaleQty(): float;

    /**
     * @return float
     */
    public function getMaxSaleQty(): float;

    /**
     * Retrieve backorders status
     *
     * @return int
     */
    public function getBackorders(): int;

    /**
     * @return float
     */
    public function getNotifyStockQty(): float;

    /**
     * Retrieve Quantity Increments data wrapper
     *
     * @return float
     */
    public function getQtyIncrements(): float;

    /**
     * @return bool
     */
    public function isEnableQtyIncrements(): bool;

    /**
     * @return bool
     */
    public function isManageStock(): bool;

    /**
     * @return string
     */
    public function getLowStockDate(): string;

    /**
     * @return bool
     */
    public function isDecimalDivided(): bool;

    /**
     * @return float
     */
    public function getStockThresholdQty(): float;
}
