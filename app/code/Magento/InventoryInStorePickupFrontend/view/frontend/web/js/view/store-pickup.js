/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'underscore',
    'jquery',
    'knockout',
    'uiRegistry',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/select-shipping-method',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_InventoryInStorePickupFrontend/js/model/pickup-locations-service',
], function(
    Component,
    _,
    $,
    ko,
    registry,
    quote,
    selectShippingMethodAction,
    checkoutData,
    shippingService,
    stepNavigator,
    pickupLocationsService
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_InventoryInStorePickupFrontend/store-pickup',
            deliveryMethodSelectorTemplate:
                'Magento_InventoryInStorePickupFrontend/delivery-method-selector',
            isVisible: false,
            isAvailable: false,
            isStorePickupSelected: false,
            rate: {
                carrier_code: 'in_store',
                method_code: 'pickup',
            },
        },
        rates: shippingService.getShippingRates(),
        inStoreMethod: null,

        initialize: function() {
            this._super();

            this.syncWithShipping();
            this.convertShippingAddress();
        },
        initObservable: function() {
            this._super().observe(['isVisible']);

            this.isStorePickupSelected = ko.pureComputed(function() {
                return _.isMatch(quote.shippingMethod(), this.rate);
            }, this);

            this.isAvailable = ko.pureComputed(function() {
                return _.findWhere(this.rates(), {
                    carrier_code: this.rate.carrier_code,
                    method_code: this.rate.method_code,
                });
            }, this);

            return this;
        },
        /**
         * Synchronize store pickup visibility with shipping step.
         */
        syncWithShipping: function() {
            var shippingStep = _.findWhere(stepNavigator.steps(), {
                code: 'shipping',
            });
            shippingStep.isVisible.subscribe(function(isShippingVisible) {
                this.isVisible(this.isAvailable && isShippingVisible);
            }, this);
            this.isVisible(this.isAvailable && shippingStep.isVisible());
        },
        selectShipping: function() {
            var nonPickupShippingMethod = _.find(
                this.rates(),
                function(rate) {
                    return (
                        rate.carrier_code !== this.rate.carrier_code &&
                        rate.method_code !== this.rate.method_code
                    );
                },
                this
            );

            this.selectShippingMethod(nonPickupShippingMethod);

            registry.async('checkoutProvider')(function(checkoutProvider) {
                checkoutProvider.set(
                    'shippingAddress',
                    quote.shippingAddress()
                );
                checkoutProvider.trigger('data.reset');
            });
        },
        selectStorePickup: function() {
            this.preselectLocation();
            this.selectShippingMethod(this.rate);
        },
        /**
         * @param {Object} shippingMethod
         */
        selectShippingMethod: function(shippingMethod) {
            selectShippingMethodAction(shippingMethod);
            checkoutData.setSelectedShippingAddress(
                quote.shippingAddress().getKey()
            );
        },
        convertShippingAddress() {
            quote.shippingAddress.subscribe(function(shippingAddress) {
                if (
                    !this.isStorePickupAddress(shippingAddress) &&
                    this.isStorePickupSelected()
                ) {
                    quote.shippingAddress(
                        $.extend({}, shippingAddress, {
                            canUseForBilling: function() {
                                return false;
                            },
                            getType: function() {
                                return 'store-pickup-address';
                            },
                        })
                    );
                }
            }, this);
        },
        preselectLocation: function() {
            if (pickupLocationsService.selectedLocation()) {
                return;
            }

            var shippingAddress = quote.shippingAddress();
            var customAttributes = shippingAddress.customAttributes || [];
            var selectedSourceCode = _.findWhere(customAttributes, {
                attribute_code: 'sourceCode',
            });

            if (selectedSourceCode) {
                pickupLocationsService
                    .getLocation(selectedSourceCode)
                    .then(function(location) {
                        pickupLocationsService.selectedLocation(location);
                    });
            } else {
                pickupLocationsService
                    .getNearbyLocations(shippingAddress)
                    .then(function(locations) {
                        var nearestLocation = locations[0];

                        if (nearestLocation) {
                            pickupLocationsService.selectForShipping(
                                nearestLocation
                            );
                        }
                    });
            }
        },
        isStorePickupAddress(address) {
            return address.getType() === 'store-pickup-address';
        },
    });
});
