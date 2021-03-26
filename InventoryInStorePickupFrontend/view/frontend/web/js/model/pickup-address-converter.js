/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(['underscore'], function (_) {
    'use strict';

    return {
        /**
         * Format address to use in store pickup
         *
         * @param {Object} address
         * @return {*}
         */
        formatAddressToPickupAddress: function (address) {
            var sourceCode = _.findWhere(address.customAttributes, {
                'attribute_code': 'sourceCode'
            });

            if (!sourceCode &&
                !_.isEmpty(address.extensionAttributes) &&
                address.extensionAttributes['pickup_location_code']
            ) {
                sourceCode = {
                    value: address.extensionAttributes['pickup_location_code']
                };
            }

            if (sourceCode && address.getType() !== 'store-pickup-address') {
                address = _.extend({}, address, {
                    saveInAddressBook: 0,

                    /**
                     * Is address can be used for billing
                     *
                     * @return {Boolean}
                     */
                    canUseForBilling: function () {
                        return false;
                    },

                    /**
                     * Returns address type
                     *
                     * @return {String}
                     */
                    getType: function () {
                        return 'store-pickup-address';
                    },

                    /**
                     * Returns address key
                     *
                     * @return {*}
                     */
                    getKey: function () {
                        return this.getType() + sourceCode.value;
                    }
                });
            }

            return address;
        }
    };
});
