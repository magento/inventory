/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'uiComponent',
    'Magento_Customer/js/customer-data',
], function(_, Component, customerData) {
    'use strict';

    var countryData = customerData.get('directory-data');

    return Component.extend({
        defaults: {
            pickupLocation: {},
            template:
                'Magento_InventoryInStorePickupFrontend/shipping-information/address-renderer/store-pickup-address',
        },
        initialize: function() {
            this._super();
            this.extractPickupLocationFromAddress(this.address());
        },

        initObservable: function() {
            return this._super().observe(['pickupLocation']);
        },

        /**
         * @param {*} countryId
         * @return {String}
         */
        getCountryName: function(countryId) {
            return countryData()[countryId] != undefined
                ? countryData()[countryId].name
                : ''; //eslint-disable-line
        },

        extractPickupLocationFromAddress(address) {
            var pickupLocationAttribute = _.findWhere(
                address.customAttributes,
                { attribute_code: 'pickupLocation' }
            );

            if (pickupLocationAttribute) {
                this.pickupLocation(pickupLocationAttribute.value);
            }
        },
    });
});
