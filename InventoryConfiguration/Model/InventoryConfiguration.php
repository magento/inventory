<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\CatalogInventory\Api\StockConfigurationInterface;

class InventoryConfiguration implements \Magento\InventoryConfigurationApi\Model\InventoryConfigurationInterface
{
    /**
     * @var StockConfigurationInterface
     */
    private $legacyStockConfiguration;

    /**
     * @param StockConfigurationInterface $legacyStockConfiguration
     */
    public function __construct(StockConfigurationInterface $legacyStockConfiguration)
    {
        $this->legacyStockConfiguration = $legacyStockConfiguration;
    }

    /**
     * @inheritdoc
     */
    public function canSubtractQty($store = null): bool
    {
        return $this->legacyStockConfiguration->canSubtractQty($store);
    }

    /**
     * @inheritdoc
     */
    public function getMinQty($store = null): float
    {
        return $this->legacyStockConfiguration->getMinQty($store);
    }

    /**
     * @inheritdoc
     */
    public function getMinSaleQty($store = null, ?int $customerGroupId = null): float
    {
        return $this->legacyStockConfiguration->getMinSaleQty($store, $customerGroupId);
    }

    /**
     * @inheritdoc
     */
    public function getMaxSaleQty($store = null): float
    {
        return $this->legacyStockConfiguration->getMaxSaleQty($store);
    }

    /**
     * @inheritdoc
     */
    public function getNotifyStockQty($store = null): float
    {
        return $this->legacyStockConfiguration->getNotifyStockQty($store);
    }

    /**
     * @inheritdoc
     */
    public function isQtyIncrementsEnabled($store = null): bool
    {
        return $this->legacyStockConfiguration->getEnableQtyIncrements($store);
    }

    /**
     * @inheritdoc
     */
    public function getQtyIncrements($store = null): float
    {
        return $this->legacyStockConfiguration->getQtyIncrements($store);
    }

    /**
     * @inheritdoc
     */
    public function getBackorders($store = null): int
    {
        return $this->legacyStockConfiguration->getBackorders($store);
    }

    /**
     * @inheritdoc
     */
    public function getManageStock($store = null): int
    {
        return $this->legacyStockConfiguration->getManageStock($store);
    }

    /**
     * @inheritdoc
     */
    public function isCanBackInStock($store = null): bool
    {
        return $this->legacyStockConfiguration->getCanBackInStock($store);
    }

    /**
     * @inheritdoc
     */
    public function isShowOutOfStock($store = null): bool
    {
        return $this->legacyStockConfiguration->isShowOutOfStock($store);
    }

    /**
     * @inheritdoc
     */
    public function isAutoReturnEnabled($store = null): bool
    {
        return $this->legacyStockConfiguration->isAutoReturnEnabled($store);
    }

    /**
     * @inheritdoc
     */
    public function isDisplayProductStockStatus($store = null): bool
    {
        return $this->legacyStockConfiguration->isDisplayProductStockStatus($store);
    }
}
