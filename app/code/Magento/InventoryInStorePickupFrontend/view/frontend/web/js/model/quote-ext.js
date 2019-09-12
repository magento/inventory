/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'Magento_InventoryInStorePickupFrontend/js/model/pickup-address-converter',
], function(ko, pickupAddressConverter) {
    'use strict';

    return function(quote) {
        var shippingAddress = quote.shippingAddress;
        /**
         * Makes sure that shipping address gets appropriate type when it points
         * to a store pickup location.
         */
        quote.shippingAddress = ko.pureComputed({
            read: function() {
                return shippingAddress();
            },
            write: function(address) {
                shippingAddress(
                    pickupAddressConverter.formatAddressToPickupAddress(address)
                );
            },
        });

        return quote;
    };
});
