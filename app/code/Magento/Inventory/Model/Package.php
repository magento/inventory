<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Inventory\Model;

class Package extends \Magento\Framework\Api\AbstractExtensibleObject
    implements PackageInterface
{

    public function getSource()
    {
        return $this->_get('source');
    }

    public function getQty()
    {
        return $this->_get('qty');
    }

    public function getItems()
    {
        return $this->_get('items');
    }

    public function getBaseSubtotal()
    {
        return $this->_get('base_subtotal');
    }

    public function getBaseSubtotalWithDiscount()
    {
        return $this->_get('base_subtotal_with_discount');
    }

    public function getWeight()
    {
        return $this->_get('weight');
    }

    public function getItemQty()
    {
        return $this->_get('item_qty');
    }

    public function getPhysicalValue()
    {
        return $this->_get('physical_value');
    }
}