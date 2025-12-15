/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */

define([
    'ko',
    'Magento_InventoryInStorePickupFrontend/js/model/pickup-address-converter'
], function (ko, pickupAddressConverter) {
    'use strict'; //eslint-disable-line

    return function (quote) {
        var shippingAddress = quote.shippingAddress;

        /**
         * Makes sure that shipping address gets appropriate type when it points
         * to a store pickup location.
         */
        quote.shippingAddress = ko.pureComputed({
            /**
             * Return quote shipping address
             */
            read: function () {
                return shippingAddress();
            },

            /**
             * Set quote shipping address
             */
            write: function (address) {
                shippingAddress(
                    pickupAddressConverter.formatAddressToPickupAddress(address)
                );
            }
        });

        return quote;
    };
});
