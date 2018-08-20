<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationExtensionInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;

class StockItemConfiguration extends AbstractExtensibleModel implements StockItemConfigurationInterface
{
    /**
     * @inheritdoc
     */
    public function getMinQty(): ?float
    {
        return $this->getData(self::MIN_QTY);
    }

    /**
     * @inheritdoc
     */
    public function setMinQty(?float $minQty): void
    {
        $this->setData(self::MIN_QTY, $minQty);
    }

    /**
     * @inheritdoc
     */
    public function getMinSaleQty(): ?float
    {
        return $this->getData(self::MIN_SALE_QTY);
    }

    /**
     * @inheritdoc
     */
    public function setMinSaleQty(?float $minSaleQty): void
    {
        $this->setData(self::MIN_SALE_QTY, $minSaleQty);
    }

    /**
     * @inheritdoc
     */
    public function getMaxSaleQty(): ?float
    {
        return $this->getData(self::MAX_SALE_QTY);
    }

    /**
     * @inheritdoc
     */
    public function setMaxSaleQty(?float $maxSaleQty): void
    {
        $this->setData(self::MAX_SALE_QTY, $maxSaleQty);
    }

    /**
     * @inheritdoc
     */
    public function getQtyIncrements(): ?float
    {
        return $this->getData(self::QTY_INCREMENTS);
    }

    /**
     * @inheritdoc
     */
    public function setQtyIncrements(?float $qtyIncrements): void
    {
        $this->setData(self::QTY_INCREMENTS, $qtyIncrements);
    }

    /**
     * @inheritdoc
     */
    public function isEnableQtyIncrements(): ?bool
    {
        return $this->getData(self::ENABLE_QTY_INCREMENTS);
    }

    /**
     * @inheritdoc
     */
    public function setEnableQtyIncrements(?bool $enableQtyIncrements): void
    {
        $this->setData(self::ENABLE_QTY_INCREMENTS, $enableQtyIncrements);
    }

    /**
     * @inheritdoc
     */
    public function isManageStock(): ?bool
    {
        return $this->getData(self::MANAGE_STOCK);
    }

    /**
     * @inheritdoc
     */
    public function setManageStock(?bool $manageStock): void
    {
        $this->setData(self::MANAGE_STOCK, $manageStock);
    }

    /**
     * @inheritdoc
     */
    public function getLowStockDate(): ?string
    {
        return $this->getData(self::LOW_STOCK_DATE);
    }

    /**
     * @inheritdoc
     */
    public function setLowStockDate(?string $lowStockDate): void
    {
        $this->setData(self::LOW_STOCK_DATE, $lowStockDate);
    }

    /**
     * @inheritdoc
     */
    public function isDecimalDivided(): ?bool
    {
        return $this->getData(self::IS_DECIMAL_DIVIDED);
    }

    /**
     * @inheritdoc
     */
    public function setIsDecimalDivided(?bool $isDecimalDivided): void
    {
        $this->setData(self::IS_DECIMAL_DIVIDED, $isDecimalDivided);
    }

    /**
     * @inheritdoc
     */
    public function getStockStatusChangedAuto(): ?bool
    {
        return $this->getData(self::STOCK_STATUS_CHANGED_AUTO);
    }

    /**
     * @inheritdoc
     */
    public function setStockStatusChangedAuto(?bool $stockStatusChangedAuto): void
    {
        $this->setData(self::STOCK_STATUS_CHANGED_AUTO, $stockStatusChangedAuto);
    }

    /**
     * @inheritdoc
     */
    public function getStockThresholdQty(): ?float
    {
        return $this->getData(self::STOCK_THRESHOLD_QTY);
    }

    /**
     * @inheritdoc
     */
    public function setStockThresholdQty(?float $stockThresholdQty): void
    {
        $this->setData(self::STOCK_THRESHOLD_QTY, $stockThresholdQty);
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes()
    {
        $extensionAttributes = $this->_getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->extensionAttributesFactory->create(StockItemConfigurationInterface::class);
            $this->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(StockItemConfigurationExtensionInterface $extensionAttributes)
    {
        $this->_setExtensionAttributes($extensionAttributes);
    }
}
