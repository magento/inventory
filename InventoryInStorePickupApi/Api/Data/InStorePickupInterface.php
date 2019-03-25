<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Api\Data;

/**
 * This class may require refactoring.
 *
 * @deprecated
 */
interface InStorePickupInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const IN_STORE_PICKUP_CODE = 'in_store_pickup';

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Magento\InventoryInStorePickupApi\Api\Data\InStorePickupExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Magento\InventoryInStorePickupApi\Api\Data\InStorePickupExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Magento\InventoryInStorePickupApi\Api\Data\InStorePickupExtensionInterface $extensionAttributes
    );
}
