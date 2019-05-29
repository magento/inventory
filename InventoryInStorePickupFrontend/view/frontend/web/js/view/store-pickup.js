/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'ko',
    'underscore',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/select-shipping-method',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/model/step-navigator',
], function(
    Component,
    ko,
    _,
    quote,
    selectShippingMethodAction,
    checkoutData,
    shippingService,
    stepNavigator
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_InventoryInStorePickupFrontend/store-pickup',
            deliveryMethodSelectorTemplate:
                'Magento_InventoryInStorePickupFrontend/delivery-method-selector',
        },
        isVisible: ko.observable(false),
        isSelected: ko.observable(false),
        rates: shippingService.getShippingRates(),
        inStoreMethod: null,

        initialize: function() {
            var self = this;
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
        },
        preselectFromQuote: function() {
            var self = this;
            quote.shippingMethod.subscribe(function(shippingMethod) {
                self.isSelected(
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
            var shipping = _.findWhere(stepNavigator.steps(), {
                code: 'shipping',
            });
            shipping.isVisible.subscribe(function(isShippingVisible) {
                self.isVisible(isShippingVisible);
            });
            self.isVisible(
                self.inStoreMethod.available && shipping.isVisible()
            );
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
        },
        selectStorePickup: function() {
            // TODO: Decide if selection logic works that way.
            this.selectShippingMethod(this.inStoreMethod);
        },
        /**
         * @param {Object} shippingMethod
         * @return {Boolean}
         */
        selectShippingMethod: function(shippingMethod) {
            var shippingRate = shippingMethod
                ? shippingMethod['carrier_code'] +
                  '_' +
                  shippingMethod['method_code']
                : null;

            selectShippingMethodAction(shippingMethod);
            checkoutData.setSelectedShippingRate(shippingRate);

            return true;
        },
    });
});
