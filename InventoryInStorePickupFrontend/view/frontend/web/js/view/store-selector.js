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
    'Magento_Checkout/js/action/set-shipping-information',
    'Magento_InventoryInStorePickupFrontend/js/model/pickup-locations-service',
], function(
    $,
    _,
    Component,
    quote,
    customer,
    stepNavigator,
    setShippingInformationAction,
    pickupLocationsService
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_InventoryInStorePickupFrontend/store-selector',
            loginFormSelector:
                '#store-selector form[data-role=email-with-possible-login]',
        },
        quoteIsVirtual: quote.isVirtual(),
        pickupLocations: pickupLocationsService.pickupLocations,

        initialize: function() {
            this._super();

            quote.shippingAddress.subscribe(console.log);

            quote.shippingAddress.subscribe(function(address) {
                pickupLocationsService.getLocations(address);
            });
            this.pickupLocations.subscribe(console.log);
        },

        /**
         * Set shipping information handler
         */
        setPickupInformation: function() {
            var address;
            if (this.validatePickupInformation()) {
                // address = $.extend(
                //     {},
                //     addressConverter.formAddressDataToQuoteAddress({
                //         firstname: 'NKD',
                //         lastname: 'Store',
                //         street: ['Schönhauser Allee 101'],
                //         city: 'Berlin',
                //         postcode: '10439',
                //         countryId: 'DE',
                //         telephone: '123123123',
                //         custom_attributes: {
                //             sourceCode: 'test-source-code',
                //         },
                //     })
                // );
                // quote.billingAddress(null);
                // selectShippingAddress(address);
                // checkoutData.setShippingAddressFromData(address);
                // checkoutData.setSelectedShippingAddress(address.getKey());
                // checkoutDataResolver.resolveBillingAddress();

                debugger;
                var shippingAddress = quote.shippingAddress();
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
