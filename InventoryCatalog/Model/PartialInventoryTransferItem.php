<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Framework\Api\AbstractSimpleObject;
use Magento\InventoryCatalogApi\Api\Data\PartialInventoryTransferItemInterface;

class PartialInventoryTransferItem extends AbstractSimpleObject implements PartialInventoryTransferItemInterface
{

    /**
     * Returns the SKU for the partial inventory transfer item.
     *
     * @return string
     */
    public function getSku(): string
    {
        return $this->_get(self::SKU);
    }

    /**
     * Sets the SKU for the partial inventory transfer item.
     *
     * @param string $sku
     */
    public function setSku(string $sku): void
    {
        $this->setData(self::SKU, $sku);
    }

    /**
     * Returns the quantity for the partial inventory transfer item.
     *
     * @return float
     */
    public function getQty(): float
    {
        return $this->_get(self::QTY);
    }

    /**
     * Sets the quantity for the partial inventory transfer item.
     *
     * @param float $qty
     */
    public function setQty(float $qty): void
    {
        $this->setData(self::QTY, $qty);
    }
}
