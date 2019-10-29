/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'uiComponent',
    'Magento_Customer/js/customer-data'
], function (_, Component, customerData) {
    'use strict';

    var countryData = customerData.get('directory-data');

    return Component.extend({
        defaults: {
            pickupLocation: {},
            template:
                'Magento_InventoryInStorePickupFrontend/shipping-information/address-renderer/store-pickup-address'
        },

        /**
         * Init component
         *
         * @return {exports}
         */
        initialize: function () {
            this._super();
            this.extractPickupLocationFromAddress(this.address());

            return this;
        },

        /**
         * Init component observable variables
         *
         * @return {*}
         */
        initObservable: function () {
            return this._super().observe(['pickupLocation']);
        },

        /**
         * @param {*} countryId
         * @return {String}
         */
        getCountryName: function (countryId) {
            return _.isUndefined(countryData()[countryId]) ?
                ''
                : countryData()[countryId].name; //eslint-disable-line
        },

        /**
         * Extract pickup location from address
         *
         * @param {Object} address
         */
        extractPickupLocationFromAddress: function (address) {
            var pickupLocationAttribute = _.findWhere(
                address.customAttributes,
                {
                'attribute_code': 'pickupLocation'
            }
            );

            if (pickupLocationAttribute) {
                this.pickupLocation(pickupLocationAttribute.value);
            }
        }
    });
});
