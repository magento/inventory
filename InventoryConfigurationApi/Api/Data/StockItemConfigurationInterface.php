<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Api\Data;

/**
 * @api
 */
interface StockItemConfigurationInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    public const BACKORDERS_NO = 0;
    public const BACKORDERS_YES_NONOTIFY = 1;
    public const BACKORDERS_YES_NOTIFY = 2;

    public const IS_QTY_DECIMAL = 'is_qty_decimal';
    public const SHOW_DEFAULT_NOTIFICATION_MESSAGE = 'show_default_notification_message';

    /*
     * Safety stock threshold, not to confuse with the one used to show the "Only X left" label on frontend
     */
    public const USE_CONFIG_MIN_QTY = 'use_config_min_qty';
    public const MIN_QTY = 'min_qty';

    /*
     * Threshold intended to show the "Only X left" label on frontend
     */
    public const USE_CONFIG_STOCK_THRESHOLD_QTY = 'use_config_stock_threshold_qty';
    public const STOCK_THRESHOLD_QTY = 'stock_threshold_qty';

    /*
     * Used to prevent to buy less than a certain qty of a product, not to confuse with the safety stock threshold
     */
    public const USE_CONFIG_MIN_SALE_QTY = 'use_config_min_sale_qty';
    public const MIN_SALE_QTY = 'min_sale_qty';

    public const USE_CONFIG_MAX_SALE_QTY = 'use_config_max_sale_qty';
    public const MAX_SALE_QTY = 'max_sale_qty';

    public const USE_CONFIG_BACKORDERS = 'use_config_backorders';
    public const BACKORDERS = 'backorders';

    public const USE_CONFIG_NOTIFY_STOCK_QTY = 'use_config_notify_stock_qty';
    public const NOTIFY_STOCK_QTY = 'notify_stock_qty';

    public const USE_CONFIG_QTY_INCREMENTS = 'use_config_qty_increments';
    public const QTY_INCREMENTS = 'qty_increments';

    public const USE_CONFIG_ENABLE_QTY_INC = 'use_config_enable_qty_inc';
    public const ENABLE_QTY_INCREMENTS = 'enable_qty_increments';

    public const USE_CONFIG_MANAGE_STOCK = 'use_config_manage_stock';
    public const MANAGE_STOCK = 'manage_stock';

    public const LOW_STOCK_DATE = 'low_stock_date';
    public const IS_DECIMAL_DIVIDED = 'is_decimal_divided';
    public const STOCK_STATUS_CHANGED_AUTO = 'stock_status_changed_auto';

    /**
     * Check if the quantity is managed as a decimal value.
     *
     * @return bool
     */
    public function isQtyDecimal(): bool;

    /**
     * Set whether the quantity is managed as a decimal value.
     *
     * @param bool $isQtyDecimal
     * @return void
     */
    public function setIsQtyDecimal(bool $isQtyDecimal): void;

    /**
     * Check if the default notification message is enabled.
     *
     * @return bool
     */
    public function isShowDefaultNotificationMessage(): bool;

    /**
     * Check if the minimum quantity uses the configuration value.
     *
     * @return bool
     */
    public function isUseConfigMinQty(): bool;

    /**
     * Set whether the minimum quantity uses the configuration value.
     *
     * @param bool $useConfigMinQty
     * @return void
     */
    public function setUseConfigMinQty(bool $useConfigMinQty): void;

    /**
     * Retrieve the minimum quantity allowed.
     *
     * @return float
     */
    public function getMinQty(): float;

    /**
     * Set the minimum quantity allowed.
     *
     * @param float $minQty
     * @return void
     */
    public function setMinQty(float $minQty): void;

    /**
     * Check if the minimum sale quantity uses the configuration value.
     *
     * @return bool
     */
    public function isUseConfigMinSaleQty(): bool;

    /**
     * Set whether the minimum sale quantity uses the configuration value.
     *
     * @param bool $useConfigMinSaleQty
     * @return void
     */
    public function setUseConfigMinSaleQty(bool $useConfigMinSaleQty): void;

    /**
     * Retrieve the minimum sale quantity allowed.
     *
     * @return float
     */
    public function getMinSaleQty(): float;

    /**
     * Set the minimum sale quantity allowed.
     *
     * @param float $minSaleQty
     * @return void
     */
    public function setMinSaleQty(float $minSaleQty): void;

    /**
     * Check if the maximum sale quantity uses the configuration value.
     *
     * @return bool
     */
    public function isUseConfigMaxSaleQty(): bool;

    /**
     * Set whether the maximum sale quantity uses the configuration value.
     *
     * @param bool $useConfigMaxSaleQty
     * @return void
     */
    public function setUseConfigMaxSaleQty(bool $useConfigMaxSaleQty): void;

    /**
     * Retrieve the maximum sale quantity allowed.
     *
     * @return float
     */
    public function getMaxSaleQty(): float;

    /**
     * Set the maximum sale quantity allowed.
     *
     * @param float $maxSaleQty
     * @return void
     */
    public function setMaxSaleQty(float $maxSaleQty): void;

    /**
     * Check if backorders use the configuration value.
     *
     * @return bool
     */
    public function isUseConfigBackorders(): bool;

    /**
     * Set whether backorders use the configuration value.
     *
     * @param bool $useConfigBackorders
     * @return void
     */
    public function setUseConfigBackorders(bool $useConfigBackorders): void;

    /**
     * Retrieve backorders status
     *
     * @return int
     */
    public function getBackorders(): int;

    /**
     * Set the backorders status.
     *
     * @param int $backOrders
     * @return void
     */
    public function setBackorders(int $backOrders): void;

    /**
     * Check if the notify stock quantity uses the configuration value.
     *
     * @return bool
     */
    public function isUseConfigNotifyStockQty(): bool;

    /**
     * Set whether the notify stock quantity uses the configuration value.
     *
     * @param bool $useConfigNotifyStockQty
     * @return void
     */
    public function setUseConfigNotifyStockQty(bool $useConfigNotifyStockQty): void;

    /**
     * Retrieve the notify stock quantity.
     *
     * @return float
     */
    public function getNotifyStockQty(): float;

    /**
     * Set the notify stock quantity.
     *
     * @param float $notifyStockQty
     * @return void
     */
    public function setNotifyStockQty(float $notifyStockQty): void;

    /**
     * Check if quantity increments use the configuration value.
     *
     * @return bool
     */
    public function isUseConfigQtyIncrements(): bool;

    /**
     * Set whether quantity increments use the configuration value.
     *
     * @param bool $useConfigQtyIncrements
     * @return void
     */
    public function setUseConfigQtyIncrements(bool $useConfigQtyIncrements): void;

    /**
     * Retrieve Quantity Increments data wrapper
     *
     * @return float
     */
    public function getQtyIncrements(): float;

    /**
     * Set the quantity increments.
     *
     * @param float $qtyIncrements
     * @return void
     */
    public function setQtyIncrements(float $qtyIncrements): void;

    /**
     * Check if enabling quantity increments uses the configuration value.
     *
     * @return bool
     */
    public function isUseConfigEnableQtyInc(): bool;

    /**
     * Set whether enabling quantity increments uses the configuration value.
     *
     * @param bool $useConfigEnableQtyInc
     * @return void
     */
    public function setUseConfigEnableQtyInc(bool $useConfigEnableQtyInc): void;

    /**
     * Check if quantity increments are enabled.
     *
     * @return bool
     */
    public function isEnableQtyIncrements(): bool;

    /**
     * Set whether quantity increments are enabled.
     *
     * @param bool $enableQtyIncrements
     * @return void
     */
    public function setEnableQtyIncrements(bool $enableQtyIncrements): void;

    /**
     * Check if stock management uses the configuration value.
     *
     * @return bool
     */
    public function isUseConfigManageStock(): bool;

    /**
     * Set whether stock management uses the configuration value.
     *
     * @param bool $useConfigManageStock
     * @return void
     */
    public function setUseConfigManageStock(bool $useConfigManageStock): void;

    /**
     * Check if stock management is enabled.
     *
     * @return bool
     */
    public function isManageStock(): bool;

    /**
     * Set whether stock management is enabled.
     *
     * @param bool $manageStock
     * @return void
     */
    public function setManageStock(bool $manageStock): void;

    /**
     * Retrieve the low stock date.
     *
     * @return string
     */
    public function getLowStockDate(): string;

    /**
     * Set the low stock date.
     *
     * @param string $lowStockDate
     * @return void
     */
    public function setLowStockDate(string $lowStockDate): void;

    /**
     * Check if decimal division is enabled.
     *
     * @return bool
     */
    public function isDecimalDivided(): bool;

    /**
     * Set whether decimal division is enabled.
     *
     * @param bool $isDecimalDivided
     * @return void
     */
    public function setIsDecimalDivided(bool $isDecimalDivided): void;

    /**
     * Check if stock status changed automatically.
     *
     * @return int
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getStockStatusChangedAuto(): bool;

    /**
     * Set whether stock status changed automatically.
     *
     * @param int $stockStatusChangedAuto
     * @return void
     */
    public function setStockStatusChangedAuto(int $stockStatusChangedAuto): void;

    /**
     * Check if the stock threshold quantity uses the configuration value.
     *
     * @return float
     */
    public function getStockThresholdQty(): float;

    /**
     * Retrieve existing extension attributes object
     *
     * @return \Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationExtensionInterface|null
     */
    public function getExtensionAttributes(): ?StockItemConfigurationExtensionInterface;

    /**
     * Set an extension attributes object
     *
     * @param \Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(
        \Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationExtensionInterface $extensionAttributes
    ): void;
}
