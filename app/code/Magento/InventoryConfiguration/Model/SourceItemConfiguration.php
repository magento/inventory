<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationExtensionInterface;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;

class SourceItemConfiguration extends AbstractExtensibleModel implements SourceItemConfigurationInterface
{
    /**
     * @inheritdoc
     */
    public function getBackorders(): ?int
    {
        return $this->getData(self::BACKORDERS);
    }

    /**
     * @inheritdoc
     */
    public function setBackorders(?int $backOrders): void
    {
        $this->setData(self::BACKORDERS, $backOrders);
    }

    /**
     * @inheritdoc
     */
    public function getNotifyStockQty(): ?float
    {
        return $this->getData(self::NOTIFY_STOCK_QTY);
    }

    /**
     * @inheritdoc
     */
    public function setNotifyStockQty(?float $notifyStockQty): void
    {
        $this->setData(self::NOTIFY_STOCK_QTY, $notifyStockQty);
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes()
    {
        $extensionAttributes = $this->_getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->extensionAttributesFactory->create(SourceItemConfigurationInterface::class);
            $this->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(SourceItemConfigurationExtensionInterface $extensionAttributes)
    {
        $this->_setExtensionAttributes($extensionAttributes);
    }
}
