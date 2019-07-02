/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/action/set-shipping-information',
], function(
    $,
    Component,
    quote,
    checkoutData,
    stepNavigator,
    addressConverter,
    checkoutDataResolver,
    selectShippingAddress,
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
            var address;
            if (this.validatePickupInformation()) {
                address = $.extend(
                    {},
                    addressConverter.formAddressDataToQuoteAddress({
                        custom_attributes: {
                            pickupLocation: {
                                code: 'test',
                                name: 'Location name',
                                street: 'Some street',
                                city: 'Some city',
                                postcode: '123123',
                                countryId: 'US',
                            },
                        },
                    })
                );
                quote.billingAddress(null);
                selectShippingAddress(address);
                checkoutData.setShippingAddressFromData(address);
                checkoutData.setSelectedShippingAddress(address.getKey());
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
