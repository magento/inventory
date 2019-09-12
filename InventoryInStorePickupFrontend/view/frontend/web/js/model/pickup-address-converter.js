/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(['underscore'], function(_) {
    'use strict';

    return {
        formatAddressToPickupAddress: function(address) {
            var sourceCode = _.findWhere(address.customAttributes, {
                attribute_code: 'sourceCode',
            });
            if (sourceCode && address.getType() !== 'store-pickup-address') {
                address = _.extend({}, address, {
                    saveInAddressBook: 0,
                    canUseForBilling: function() {
                        return false;
                    },
                    getType: function() {
                        return 'store-pickup-address';
                    },
                    getKey: function() {
                        return this.getType() + sourceCode.value;
                    },
                });
            }

            return address;
        },
    };
});
