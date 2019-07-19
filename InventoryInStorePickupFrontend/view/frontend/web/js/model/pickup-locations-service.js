/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko',
    'Magento_InventoryInStorePickupFrontend/js/model/resource-url-manager',
    'mage/storage',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_InventoryInStorePickupFrontend/js/model/pickup-locations-registry',
    'Magento_Checkout/js/model/error-processor',
], function(
    ko,
    resourceUrlManager,
    storage,
    shippingService,
    pickupLocationsRegistry,
    errorProcessor
) {
    'use strict';

    var pickupLocations = ko.observable([]);

    return {
        pickupLocations: pickupLocations,
        /**
         * Get shipping rates for specified address.
         * @param {Object} address
         */
        getLocations: function(address) {
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
                        pickupLocationsRegistry.set(
                            address.getCacheKey(),
                            result
                        );
                        pickupLocations(result);
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
    };
});
