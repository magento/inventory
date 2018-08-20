<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Api;

interface InventoryConfigurationInterface
{
    const BACKORDERS_NO = 0;
    const BACKORDERS_YES_NONOTIFY = 1;
    const BACKORDERS_YES_NOTIFY = 2;

    const IS_QTY_DECIMAL = 'is_qty_decimal';
    const SHOW_DEFAULT_NOTIFICATION_MESSAGE = 'show_default_notification_message';

    /*
     * Safety stock threshold, not to confuse with the one used to show the "Only X left" label on frontend
     */
    const MIN_QTY = 'min_qty';

    /*
     * Threshold intended to show the "Only X left" label on frontend
     */
    const STOCK_THRESHOLD_QTY = 'stock_threshold_qty';

    /*
     * Used to prevent to buy less than a certain qty of a product, not to confuse with the safety stock threshold
     */
    const MIN_SALE_QTY = 'min_sale_qty';
    const MAX_SALE_QTY = 'max_sale_qty';
    const BACKORDERS = 'backorders';
    const NOTIFY_STOCK_QTY = 'notify_stock_qty';
    const QTY_INCREMENTS = 'qty_increments';
    const ENABLE_QTY_INCREMENTS = 'enable_qty_increments';
    const MANAGE_STOCK = 'manage_stock';
    const LOW_STOCK_DATE = 'low_stock_date';
    const IS_DECIMAL_DIVIDED = 'is_decimal_divided';

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
