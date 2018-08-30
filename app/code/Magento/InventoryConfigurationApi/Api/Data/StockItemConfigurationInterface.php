<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * @api
 */
interface StockItemConfigurationInterface extends ExtensibleDataInterface
{
    /**
     * Default source configuration path
     */
    const XML_PATH_MIN_QTY = 'cataloginventory/item_options/min_qty';
    const XML_PATH_MIN_SALE_QTY = 'cataloginventory/item_options/min_sale_qty';
    const XML_PATH_MAX_SALE_QTY = 'cataloginventory/item_options/max_sale_qty';
    const XML_PATH_MANAGE_STOCK = 'cataloginventory/item_options/manage_stock';
    const XML_PATH_ENABLE_QTY_INCREMENTS = 'cataloginventory/item_options/enable_qty_increments';
    const XML_PATH_QTY_INCREMENTS = 'cataloginventory/item_options/qty_increments';
    const XML_PATH_STOCK_THRESHOLD_QTY = 'cataloginventory/options/stock_threshold_qty';

    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const MIN_QTY = 'min_qty';
    const MIN_SALE_QTY = 'min_sale_qty';
    const MAX_SALE_QTY = 'max_sale_qty';
    const QTY_INCREMENTS = 'qty_increments';
    const ENABLE_QTY_INCREMENTS = 'enable_qty_increments';
    const MANAGE_STOCK = 'manage_stock';
    const LOW_STOCK_DATE = 'low_stock_date';
    const IS_QTY_DECIMAL = 'is_qty_decimal';
    const IS_DECIMAL_DIVIDED = 'is_decimal_divided';
    const STOCK_STATUS_CHANGED_AUTO = 'stock_status_changed_auto';
    const STOCK_THRESHOLD_QTY = 'stock_threshold_qty';
    /**#@-*/

    /**
     * @return float|null
     */
    public function getMinQty(): ?float;

    /**
     * @param float|null $minQty
     * @return void
     */
    public function setMinQty(?float $minQty): void;

    /**
     * @return float|null
     */
    public function getMinSaleQty(): ?float;

    /**
     * @param float|null $minSaleQty
     * @return void
     */
    public function setMinSaleQty(?float $minSaleQty): void;

    /**
     * @return float|null
     */
    public function getMaxSaleQty(): ?float;

    /**
     * @param float|null $maxSaleQty
     * @return void
     */
    public function setMaxSaleQty(?float $maxSaleQty): void;

    /**
     * @return float|null
     */
    public function getQtyIncrements(): ?float;

    /**
     * @param float|null $qtyIncrements
     * @return void
     */
    public function setQtyIncrements(?float $qtyIncrements): void;

    /**
     * @return bool|null
     */
    public function isEnableQtyIncrements(): ?bool;

    /**
     * @param bool|null $enableQtyIncrements
     * @return void
     */
    public function setEnableQtyIncrements(?bool $enableQtyIncrements): void;

    /**
     * @return bool|null
     */
    public function isManageStock(): ?bool;

    /**
     * @param bool|null $manageStock
     * @return void
     */
    public function setManageStock(?bool $manageStock): void;

    /**
     * @return string|null
     */
    public function getLowStockDate(): ?string;

    /**
     * @param string|null $lowStockDate
     * @return void
     */
    public function setLowStockDate(?string $lowStockDate): void;

    /**
     * @return bool|null
     */
    public function isQtyDecimal(): ?bool;

    /**
     * @param bool|null $isQtyDecimal
     * @return void
     */
    public function setIsQtyDecimal(?bool $isQtyDecimal): void;

    /**
     * @return bool|null
     */
    public function isDecimalDivided(): ?bool;

    /**
     * @param bool|null $isDecimalDivided
     * @return void
     */
    public function setIsDecimalDivided(?bool $isDecimalDivided): void;

    /**
     * @return bool|null
     */
    public function getStockStatusChangedAuto(): ?bool;

    /**
     * @param bool|null $stockStatusChangedAuto
     * @return void
     */
    public function setStockStatusChangedAuto(?bool $stockStatusChangedAuto): void;

    /**
     * @return float|null
     */
    public function getStockThresholdQty(): ?float;

    /**
     * @param float|null $stockThresholdQty
     * @return void
     */
    public function setStockThresholdQty(?float $stockThresholdQty): void;

    /**
     * Retrieve existing extension attributes object
     *
     * Null for return is specified for proper work SOAP requests parser
     *
     * @return \Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object
     *
     * @param \Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(StockItemConfigurationExtensionInterface $extensionAttributes);
}
