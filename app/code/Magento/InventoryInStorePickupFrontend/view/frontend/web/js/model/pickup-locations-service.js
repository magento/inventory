/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'knockout',
    'Magento_InventoryInStorePickupFrontend/js/model/resource-url-manager',
    'mage/storage',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/action/select-shipping-address',
], function(
    $,
    ko,
    resourceUrlManager,
    storage,
    shippingService,
    errorProcessor,
    customerData,
    checkoutData,
    addressConverter,
    selectShippingAddressAction
) {
    'use strict';

    var websiteCode = window.checkoutConfig.websiteCode;
    var countryData = customerData.get('directory-data');

    return {
        selectedLocation: ko.observable(null),
        /**
         * Get shipping rates for specified address.
         * @param {Object} address
         */
        getLocation: function(sourceCode) {
            var self = this;
            var serviceUrl = resourceUrlManager.getUrlForPickupLocation(
                'website',
                websiteCode,
                sourceCode
            );
            shippingService.isLoading(true);

            return storage
                .get(serviceUrl, {}, false)
                .then(function(address) {
                    shippingService.isLoading(false);

                    return self.formatAddress(address);
                })
                .fail(function(response) {
                    errorProcessor.process(response);
                    return [];
                });
        },
        /**
         * Get all pickup locations defined for given sales channel.
         */
        getLocations: function() {
            var self = this;
            var serviceUrl = resourceUrlManager.getUrlForPickupLocationsAssignedToSalesChannel(
                'website',
                websiteCode
            );

            shippingService.isLoading(true);

            return storage
                .get(serviceUrl, {}, false)
                .then(function(result) {
                    return _.map(result.items, function(address) {
                        return self.formatAddress(address);
                    });
                })
                .fail(function(response) {
                    errorProcessor.process(response);
                    return [];
                })
                .always(function() {
                    shippingService.isLoading(false);
                });
        },
        /**
         * Get nearby pickup locations based on given search criteria.
         * @param {object} searchCriteria Search criteria object.
         * @see Magento/InventoryInStorePickup/Model/SearchCriteria/GetNearbyLocationsCriteria.php
         */
        getNearbyLocations: function(searchCriteria) {
            var self = this;
            var query = {
                searchCriteria: searchCriteria,
            };
            var serviceUrl = resourceUrlManager.getUrlForNearbyPickupLocations(
                'website',
                websiteCode,
                query
            );
            shippingService.isLoading(true);

            return storage
                .get(serviceUrl, {}, false)
                .then(function(result) {
                    return _.map(result.items, function(address) {
                        return self.formatAddress(address);
                    });
                })
                .fail(function(response) {
                    errorProcessor.process(response);
                })
                .always(function() {
                    shippingService.isLoading(false);
                });
        },
        selectForShipping: function(location) {
            var address = $.extend(
                {},
                addressConverter.formAddressDataToQuoteAddress({
                    firstname: location.name,
                    lastname: 'Store',
                    street: location.street,
                    city: location.city,
                    postcode: location.postcode,
                    country_id: location.country_id,
                    telephone: location.telephone,
                    region_id: location.region_id,
                    custom_attributes: {
                        sourceCode: location.source_code,
                    },
                }),
                {
                    canUseForBilling: function() {
                        return false;
                    },
                    getType: function() {
                        return 'store-pickup-address';
                    },
                }
            );

            this.selectedLocation(location);
            selectShippingAddressAction(address);
            checkoutData.setSelectedShippingAddress(address.getKey());
        },
        /**
         * Formats address returned by REST endpoint to match checkout address field naming.
         * @param {object} address Address object returned by REST endpoint.
         */
        formatAddress: function(address) {
            return {
                name: address.name,
                description: address.description,
                latitude: address.latitude,
                longitude: address.longitude,
                street: [address.street],
                city: address.city,
                postcode: address.postcode,
                country_id: address.country_id,
                country: this.getCountryName(address.country_id),
                telephone: address.phone,
                region_id: address.region_id,
                region: this.getRegionName(
                    address.country_id,
                    address.region_id
                ),
                source_code: address.source_code,
            };
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
    };
});
