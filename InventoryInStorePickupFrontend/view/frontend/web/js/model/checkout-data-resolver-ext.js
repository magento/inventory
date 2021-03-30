/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'mage/utils/wrapper',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/model/address-converter',
    'Magento_InventoryInStorePickupFrontend/js/model/pickup-address-converter'
], function (
    wrapper,
    checkoutData,
    selectShippingAddress,
    addressConverter,
    pickupAddressConverter
) {
    'use strict';

    return function (checkoutDataResolver) {
        checkoutDataResolver.resolveShippingAddress = wrapper.wrapSuper(
            checkoutDataResolver.resolveShippingAddress,
            function () {
                var shippingAddress,
                    pickUpAddress;

                if (checkoutData.getSelectedPickupAddress() && checkoutData.getSelectedShippingAddress()) {
                    shippingAddress = addressConverter.formAddressDataToQuoteAddress(
                        checkoutData.getSelectedPickupAddress()
                    );
                    pickUpAddress = pickupAddressConverter.formatAddressToPickupAddress(
                        shippingAddress
                    );

                    if (pickUpAddress.getKey() === checkoutData.getSelectedShippingAddress()) {
                        selectShippingAddress(pickUpAddress);

                        return;
                    }
                }
                this._super();
            });

        return checkoutDataResolver;
    };
});
