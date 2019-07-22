/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/customer-data',
    'Magento_InventoryInStorePickupFrontend/js/model/pickup-locations-service',
], function($, ko, Component, quote, customerData, pickupLocationsService) {
    'use strict';

    var countryData = customerData.get('directory-data');

    return Component.extend({
        defaults: {
            template:
                'Magento_InventoryInStorePickupFrontend/pickup-location-address/address-renderer/default',
        },

        initialize: function() {
            this._super();
        },

        /** @inheritdoc */
        initObservable: function() {
            this._super();
            this.isSelected = ko.computed(function() {
                var sourceCode,
                    isSelected = false,
                    shippingAddress = quote.shippingAddress();

                if (shippingAddress && shippingAddress.customAttributes) {
                    sourceCode = _.findWhere(shippingAddress.customAttributes, {
                        attribute_code: 'sourceCode',
                    });
                    isSelected =
                        sourceCode &&
                        sourceCode.value === this.address().source_code;
                }

                return isSelected;
            }, this);

            return this;
        },

        /**
         * @param {String} countryId
         * @return {String}
         */
        getCountryName: function(countryId) {
            return countryData()[countryId] != undefined
                ? countryData()[countryId].name
                : ''; //eslint-disable-line
        },

        /**
         * Returns region name based on given country and region identifiers.
         * @param {string} countryId Country identifier.
         * @param {string} regionId Region identifier.
         */
        getRegionName: function(countryId, regionId) {
            var regions = countryData()[countryId]
                ? countryData()[countryId].regions
                : null;

            return regions && regions[regionId] ? regions[regionId].name : '';
        },

        /** Set selected customer shipping address  */
        selectAddress: function() {
            pickupLocationsService.selectForShipping(this.address());
        },
    });
});
