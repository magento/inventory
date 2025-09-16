<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Plugin\CatalogInventory\Model\System\Config\Backend\Minqty;

use Magento\CatalogInventory\Model\System\Config\Backend\Minqty;

class AllowNegativeMinQtyInConfigPlugin
{
    /**
     * Allow min_qty to be assigned a value below 0.
     *
     * @param Minqty $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundBeforeSave(
        Minqty $subject,
        callable $proceed
    ) {
        $originalMinQty = $proceed();
        $originalMinQty->setValue($subject->getFieldsetDataValue('min_qty'));
        return $originalMinQty;
    }
}
