/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'uiComponent',
    'uiRegistry',
    'Magento_Ui/js/modal/modal',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/action/set-shipping-information',
    'Magento_InventoryInStorePickupFrontend/js/model/pickup-locations-service',
], function(
    $,
    _,
    Component,
    registry,
    modal,
    quote,
    customer,
    stepNavigator,
    shippingService,
    setShippingInformationAction,
    pickupLocationsService
) {
    'use strict';

    var popup;

    return Component.extend({
        defaults: {
            template: 'Magento_InventoryInStorePickupFrontend/store-selector',
            selectedLocationTemplate:
                'Magento_InventoryInStorePickupFrontend/store-selector/selected-location',
            storeSelectorPopupTemplate:
                'Magento_InventoryInStorePickupFrontend/store-selector/popup',
            storeSelectorPopupItemTemplate:
                'Magento_InventoryInStorePickupFrontend/store-selector/popup-item',
            loginFormSelector:
                '#store-selector form[data-role=email-with-possible-login]',
        },
        selectedLocation: pickupLocationsService.selectedLocation,
        quoteIsVirtual: quote.isVirtual(),
        nearbyLocations: [],
        isLoading: shippingService.isLoading,

        initObservable: function() {
            return this._super().observe(['nearbyLocations']);
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

                registry.async('checkoutProvider')(function(checkoutProvider) {
                    checkoutProvider.set('shippingAddress', shippingAddress);

                    setShippingInformationAction().done(function() {
                        stepNavigator.next();
                    });
                });
            }
        },
        /**
         * @return {*}
         */
        getPopup: function() {
            if (!popup) {
                // this.popUpList.options.modalCloseBtnHandler = this.onClosePopUp.bind(this);
                // this.popUpList.options.keyEventHandlers = {
                //     escapeKey: this.onClosePopUp.bind(this)
                // };

                popup = modal(
                    this.popUpList.options,
                    $(this.popUpList.element)
                );
            }

            return popup;
        },
        openPopup: function() {
            var self = this;

            pickupLocationsService
                .getNearbyLocations(quote.shippingAddress())
                .then(function(locations) {
                    self.nearbyLocations(locations);
                });
            this.getPopup().openModal();
        },
        selectPickupLocation: function(location) {
            pickupLocationsService.selectForShipping(location);
            popup.closeModal();
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
