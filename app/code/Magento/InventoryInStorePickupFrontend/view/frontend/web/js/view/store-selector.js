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
    'Magento_Checkout/js/action/create-shipping-address',
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
    createShippingAddress,
    setShippingInformationAction,
    pickupLocationsService
) {
    'use strict';

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
        searchQuery: '',
        nearbyLocations: [],
        isLoading: shippingService.isLoading,
        popup: null,

        initialize: function() {
            this._super();

            var updateNearbyLocations = _.debounce(function(searchQuery) {
                var postcode, city;
                city = searchQuery.replace(/(\d+[\-]?\d+)/, function(match) {
                    postcode = match;

                    return '';
                });

                this.updateNearbyLocations(
                    createShippingAddress({
                        city: city,
                        postcode: postcode,
                        country_id: quote.shippingAddress().countryId,
                    })
                );
            }, 300).bind(this);
            this.searchQuery.subscribe(updateNearbyLocations);
        },

        initObservable: function() {
            return this._super().observe(['nearbyLocations', 'searchQuery']);
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
            var self = this;

            if (!this.popup) {
                // this.popUpList.options.modalCloseBtnHandler = this.onClosePopUp.bind(this);
                // this.popUpList.options.keyEventHandlers = {
                //     escapeKey: this.onClosePopUp.bind(this)
                // };

                this.popup = modal(
                    this.popUpList.options,
                    $(this.popUpList.element)
                );
            }

            return this.popup;
        },
        openPopup: function() {
            this.updateNearbyLocations(quote.shippingAddress());
            this.getPopup().openModal();
        },
        selectPickupLocation: function(location) {
            pickupLocationsService.selectForShipping(location);
            this.getPopup().closeModal();
        },
        isPickupLocationSelected: function(location) {
            return _.isEqual(this.selectedLocation(), location);
        },
        updateNearbyLocations(address) {
            var self = this;

            return pickupLocationsService
                .getNearbyLocations(address)
                .then(function(locations) {
                    self.nearbyLocations(locations);
                });
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
