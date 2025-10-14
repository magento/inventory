<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Plugin\Checkout;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Quote\Api\Data\AddressInterface;

/**
 * Plugin to handle store pickup address logic in ShippingInformationManagement
 */
class ShippingInformationManagementPlugin
{
    /**
     * Before plugin for saveAddressInformation to handle store pickup billing address logic
     *
     * @param ShippingInformationManagement $subject
     * @param int $cartId
     * @param ShippingInformationInterface $addressInformation
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSaveAddressInformation(
        ShippingInformationManagement $subject,
        int                           $cartId,
        ShippingInformationInterface  $addressInformation
    ): array {
        $address = $addressInformation->getShippingAddress();
        $billingAddress = $addressInformation->getBillingAddress();

        if ($this->isPickupStoreShipping($address) && $this->isBillingAddressIncomplete($billingAddress)) {
            $addressInformation->setBillingAddress($address);
        }

        return [$cartId, $addressInformation];
    }

    /**
     * Check if shipping method is in-store pickup
     *
     * @param AddressInterface|null $shippingAddress
     * @return bool
     */
    private function isPickupStoreShipping(?AddressInterface $shippingAddress): bool
    {
        if (!$shippingAddress) {
            return false;
        }
        $extensionAttributes = $shippingAddress->getExtensionAttributes();
        if ($extensionAttributes && $extensionAttributes->getPickupLocationCode()) {
            return true;
        }
        return false;
    }

    /**
     * Check if billing address is incomplete (missing required fields)
     *
     * @param AddressInterface|null $billingAddress
     * @return bool
     */
    private function isBillingAddressIncomplete(?AddressInterface $billingAddress): bool
    {
        if (!$billingAddress) {
            return true;
        }
        $requiredFields = [
            'firstname',
            'lastname',
            'street',
            'city',
            'postcode',
            'telephone',
            'regionId',
            'countryId'
        ];
        foreach ($requiredFields as $field) {
            $getter = 'get' . ucfirst($field);
            if (method_exists($billingAddress, $getter) && !$billingAddress->$getter()) {
                return true;
            }
        }
        return false;
    }
}
