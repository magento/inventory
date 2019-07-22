/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_InventoryInStorePickupFrontend/js/model/resource-url-manager',
    'mage/storage',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_InventoryInStorePickupFrontend/js/model/pickup-locations',
    'Magento_InventoryInStorePickupFrontend/js/model/pickup-locations-registry',
    'Magento_Checkout/js/model/error-processor',

    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/model/checkout-data-resolver',
], function(
    $,
    resourceUrlManager,
    storage,
    quote,
    shippingService,
    pickupLocations,
    pickupLocationsRegistry,
    errorProcessor,

    checkoutData,
    addressConverter,
    selectShippingAddress,
    checkoutDataResolver
) {
    'use strict';

    return {
        /**
         * Get shipping rates for specified address.
         * @param {Object} address
         */
        getLocations: function(address) {
            var self = this;
            var cache, serviceUrl;

            shippingService.isLoading(true);
            cache = pickupLocationsRegistry.get(address.getCacheKey());
            serviceUrl = resourceUrlManager.getUrlForPickupLocationsAssignedToSalesChannel(
                'website',
                'base'
            );

            if (cache) {
                pickupLocations(cache);
                shippingService.isLoading(false);
            } else {
                storage
                    .get(serviceUrl, {}, false)
                    .done(function(result) {
                        var addresses = _.map(result, function(address) {
                            return self.formatAddress(address);
                        });

                        pickupLocationsRegistry.set(
                            address.getCacheKey(),
                            addresses
                        );
                        pickupLocations(addresses);
                    })
                    .fail(function(response) {
                        pickupLocations([]);
                        errorProcessor.process(response);
                    })
                    .always(function() {
                        shippingService.isLoading(false);
                    });
            }
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
                })
            );
            // quote.billingAddress(null);
            // selectShippingAddress(address);
            // checkoutData.setShippingAddressFromData(address);
            // checkoutData.setSelectedShippingAddress(address.getKey());
            // checkoutDataResolver.resolveBillingAddress();
            selectShippingAddressAction(address);
            checkoutData.setSelectedShippingAddress(address.getKey());
        },
        /**
         * Formats address returned by REST endpoint to match checkout address field naming.
         * @param {object} address Address object returned by REST endpoint.
         */
        formatAddress(address) {
            return {
                name: address.name,
                description: address.description,
                latitude: address.latitude,
                longitude: address.longitude,
                street: [address.street],
                city: address.city,
                postcode: address.postcode,
                country_id: address.country_id,
                telephone: address.phone,
                region_id: address.region_id,
                source_code: address.source_code,
            };
        },
    };
});
