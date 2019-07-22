/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'ko',
    'underscore',
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/select-shipping-method',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_InventoryInStorePickupFrontend/js/model/pickup-locations',
    'Magento_InventoryInStorePickupFrontend/js/model/pickup-locations-service',
], function(
    Component,
    ko,
    _,
    $,
    quote,
    selectShippingMethodAction,
    checkoutData,
    shippingService,
    stepNavigator,
    addressList,
    selectShippingAddressAction,
    pickupLocations,
    pickupLocationsService
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_InventoryInStorePickupFrontend/store-pickup',
            deliveryMethodSelectorTemplate:
                'Magento_InventoryInStorePickupFrontend/delivery-method-selector',
            isVisible: false,
            isStorePickupSelected: false,
        },
        rates: shippingService.getShippingRates(),
        inStoreMethod: null,

        initialize: function() {
            this._super();

            // TODO: Decide how will configuration be provided.
            var checkoutConfig = window.checkoutConfig;
            this.inStoreMethod = checkoutConfig.storePickup || {
                amount: 0,
                available: true,
                base_amount: 0,
                carrier_code: 'in_store',
                carrier_title: 'In Store',
                error_message: '',
                method_code: 'pickup',
                method_title: 'Pickup',
                price_excl_tax: 0,
                price_incl_tax: 0,
            };

            this.preselectFromQuote();
            this.syncWithShipping();
            this.convertShippingAddress();
        },
        initObservable: function() {
            return this._super().observe([
                'isVisible',
                'isStorePickupSelected',
            ]);
        },
        preselectFromQuote: function() {
            var self = this;
            quote.shippingMethod.subscribe(function(shippingMethod) {
                self.isStorePickupSelected(
                    shippingMethod &&
                        shippingMethod.carrier_code ===
                            self.inStoreMethod.carrier_code &&
                        shippingMethod.method_code ===
                            self.inStoreMethod.method_code
                );
            });
        },
        /**
         * Synchronize store pickup visibility with shipping step.
         */
        syncWithShipping: function() {
            var self = this;
            var inStoreMethod = this.inStoreMethod;
            var shippingStep = _.findWhere(stepNavigator.steps(), {
                code: 'shipping',
            });
            shippingStep.isVisible.subscribe(function(isShippingVisible) {
                self.isVisible(isShippingVisible);
            });
            this.isVisible(inStoreMethod.available && shippingStep.isVisible());
        },
        selectShipping: function() {
            var inStoreMethod = this.inStoreMethod;
            var shippingMethod = _.find(this.rates(), function(rate) {
                return (
                    rate.carrier_code !== inStoreMethod.carrier_code &&
                    rate.method_code !== inStoreMethod.method_code
                );
            });
            this.selectShippingMethod(shippingMethod);

            var shippingAddress = quote.shippingAddress();
            if (this.isStorePickupAddress(shippingAddress)) {
                var defaultShippingAddress = _.find(addressList(), function(
                    address
                ) {
                    return address.isDefaultShipping();
                });

                selectShippingAddressAction(defaultShippingAddress);
                checkoutData.setSelectedShippingAddress(
                    defaultShippingAddress.getKey()
                );
            }
        },
        selectStorePickup: function() {
            this.selectShippingMethod(this.inStoreMethod);

            var subscription = pickupLocations.subscribe(function(locations) {
                var nearestPickupLocation = locations[0];
                if (nearestPickupLocation) {
                    pickupLocationsService.selectForShipping(
                        nearestPickupLocation
                    );
                }
                subscription.dispose();
            });
            pickupLocationsService.getLocations(quote.shippingAddress());
        },
        /**
         * @param {Object} shippingMethod
         */
        selectShippingMethod: function(shippingMethod) {
            var shippingRate = shippingMethod
                ? shippingMethod['carrier_code'] +
                  '_' +
                  shippingMethod['method_code']
                : null;

            selectShippingMethodAction(shippingMethod);
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
        isStorePickupAddress(address) {
            return address.getType() === 'store-pickup-address';
        },
    });
});
