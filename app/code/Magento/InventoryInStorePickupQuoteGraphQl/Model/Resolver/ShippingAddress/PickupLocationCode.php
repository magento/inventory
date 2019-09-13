<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuoteGraphQl\Model\Resolver\ShippingAddress;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\Quote\Address;

/**
 * @inheritdoc
 */
class PickupLocationCode implements \Magento\Framework\GraphQl\Query\ResolverInterface
{
    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        /** @var Address $address */
        $address = $value['model'];
        $pickupLocation = null;

        if ($address->getExtensionAttributes()) {
            $pickupLocation = $address->getExtensionAttributes()->getPickupLocationCode();
        }

        return $pickupLocation;
    }
}
