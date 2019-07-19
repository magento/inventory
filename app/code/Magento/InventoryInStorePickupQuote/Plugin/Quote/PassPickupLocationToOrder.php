<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Plugin\Quote;

use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\ToOrder;

/**
 * Pass Pickup Location code to the Order from Quote Address.
 *
 * @TODO Move logic to fieldset.xml when issue will be resolved in core..
 * @see Please check issue in core for more details: https://github.com/magento/magento2/issues/23386.
 */
class PassPickupLocationToOrder
{
    private const ORDER_FIELD_NAME = 'extension_attribute_pickup_location_code_pickup_location_code';

    /**
     * Add Pickup Location code to the Order from Quote Address.
     *
     * @param ToOrder $subject
     * @param Address $address
     * @param array $data
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeConvert(ToOrder $subject, Address $address, array $data = []): array
    {
        $extension = $address->getExtensionAttributes();

        if ($extension && $extension->getPickupLocationCode()) {
            $data[self::ORDER_FIELD_NAME] = $extension->getPickupLocationCode();
        }

        return [$address, $data];
    }
}
