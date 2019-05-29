/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Checkout/js/action/set-shipping-information',
], function(
    $,
    Component,
    quote,
    stepNavigator,
    checkoutDataResolver,
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

        /**
         * Set shipping information handler
         */
        setPickupInformation: function() {
            if (this.validatePickupInformation()) {
                quote.billingAddress(null);
                checkoutDataResolver.resolveBillingAddress();
                setShippingInformationAction().done(function() {
                    stepNavigator.next();
                });
            }
        },

        validatePickupInformation: function() {
            var emailValidationResult,
                loginFormSelector = this.loginFormSelector;

            $(loginFormSelector).validation();
            emailValidationResult = Boolean(
                $(loginFormSelector + ' input[name=username]').valid()
            );

            if (!emailValidationResult) {
                $(this.loginFormSelector + ' input[name=username]').focus();

                return false;
            }

            return true;
        },
    });
});
