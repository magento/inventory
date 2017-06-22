<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Inventory\Model;

interface PackageInterface
{
    /**
     * @return \Magento\InventoryApi\Api\Data\SourceInterface
     */
    public function getSource();

    /**
     * @return \Magento\Quote\Model\Quote\Item[]
     */
    public function getItems();

    /**
     * @return \Magento\Quote\Model\Quote\Address
     */
    public function getAddress();

    /**
     * @param $address \Magento\Quote\Model\Quote\Address
     * @return $this
     */
    public function setAddress($address);

    public function getQty();

    public function getBaseSubtotal();

    public function getBaseSubtotalWithDiscount();

    public function getWeight();

    public function getItemQty();

    public function getPhysicalValue();
}