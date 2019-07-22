/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/action/set-shipping-information',
    'Magento_InventoryInStorePickupFrontend/js/model/pickup-locations',
], function(
    $,
    _,
    Component,
    quote,
    customer,
    stepNavigator,
    shippingService,
    setShippingInformationAction
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_InventoryInStorePickupFrontend/store-selector',
            loginFormSelector:
                '#store-selector form[data-role=email-with-possible-login]',
        },
        quoteIsVirtual: quote.isVirtual(),
        isLoading: shippingService.isLoading,

        initialize: function() {
            this._super();
        },
        /**
         * Set shipping information handler
         */
        setPickupInformation: function() {
            var shippingAddress;
            if (this.validatePickupInformation()) {
                shippingAddress = quote.shippingAddress();
                if (shippingAddress.extension_attributes === undefined) {
                    shippingAddress.extension_attributes = {};
                }

                var sourceCode = _.findWhere(shippingAddress.customAttributes, {
                    attribute_code: 'sourceCode',
                });

                shippingAddress.extension_attributes.pickup_location_code =
                    sourceCode.value;

                setShippingInformationAction().done(function() {
                    stepNavigator.next();
                });
            }
        },

        validatePickupInformation: function() {
            var emailValidationResult,
                loginFormSelector = this.loginFormSelector;

            if (!customer.isLoggedIn()) {
                $(loginFormSelector).validation();
                emailValidationResult = Boolean(
                    $(loginFormSelector + ' input[name=username]').valid()
                );

                if (!emailValidationResult) {
                    $(this.loginFormSelector + ' input[name=username]').focus();

                    return false;
                }
            }

            return true;
        },
    });
});
