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
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/action/set-shipping-information',
    'Magento_InventoryInStorePickupFrontend/js/model/pickup-locations-service',
    'Magento_Checkout/js/checkout-data'
], function (
    $,
    _,
    Component,
    registry,
    modal,
    quote,
    customer,
    stepNavigator,
    addressConverter,
    setShippingInformationAction,
    pickupLocationsService,
    checkoutData
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
            defaultCountryId: window.checkoutConfig.defaultCountryId,
            delimiter: window.checkoutConfig.storePickupApiSearchTermDelimiter,
            selectedLocation: pickupLocationsService.selectedLocation,
            quoteIsVirtual: quote.isVirtual,
            searchQuery: '',
            nearbyLocations: null,
            isLoading: pickupLocationsService.isLoading,
            popup: null,
            searchDebounceTimeout: 300,
            imports: {
                nearbySearchRadius: '${ $.parentName }:nearbySearchRadius',
                nearbySearchLimit: '${ $.parentName }:nearbySearchLimit'
            }
        },

        /**
         * Init component
         *
         * @return {exports}
         */
        initialize: function () {
            var updateNearbyLocations, country;

            this._super();

            updateNearbyLocations = _.debounce(function (searchQuery) {
                country = quote.shippingAddress() && quote.shippingAddress().countryId ?
                    quote.shippingAddress().countryId : this.defaultCountryId;
                searchQuery = this.getSearchTerm(searchQuery, country);
                this.updateNearbyLocations(searchQuery);
            }, this.searchDebounceTimeout).bind(this);
            this.searchQuery.subscribe(updateNearbyLocations);

            return this;
        },

        /**
         * Init component observable variables
         *
         * @return {exports}
         */
        initObservable: function () {
            return this._super().observe(['nearbyLocations', 'searchQuery']);
        },

        /**
         * Set shipping information handler
         */
        setPickupInformation: function () {
            var shippingAddress = quote.shippingAddress();

            if (this.validatePickupInformation()) {
                shippingAddress = addressConverter.quoteAddressToFormAddressData(shippingAddress);
                checkoutData.setShippingAddressFromData(shippingAddress);
                setShippingInformationAction().done(function () {
                    stepNavigator.next();
                });
            }
        },

        /**
         * @return {*}
         */
        getPopup: function () {
            if (!this.popup) {
                this.popup = modal(
                    this.popUpList.options,
                    $(this.popUpList.element)
                );
            }

            return this.popup;
        },

        /**
         * Get Search Term from search query and country.
         *
         * @param {String} searchQuery
         * @param {String} country
         * @returns {String}
         */
        getSearchTerm: function (searchQuery, country) {
            return searchQuery ? searchQuery + this.delimiter + country : searchQuery;
        },

        /**
         * @returns void
         */
        openPopup: function () {
            var shippingAddress = quote.shippingAddress(),
                country = shippingAddress.countryId ? shippingAddress.countryId :
                this.defaultCountryId,
                searchTerm = '';

            this.getPopup().openModal();

            if (shippingAddress.city && shippingAddress.postcode) {
                searchTerm = this.getSearchTerm(shippingAddress.postcode, country);
            }

            this.updateNearbyLocations(searchTerm);
        },

        /**
         * @param {Object} location
         * @returns void
         */
        selectPickupLocation: function (location) {
            pickupLocationsService.selectForShipping(location);
            this.getPopup().closeModal();
        },

        /**
         * @param {Object} location
         * @returns {*|Boolean}
         */
        isPickupLocationSelected: function (location) {
            return _.isEqual(this.selectedLocation(), location);
        },

        /**
         * @param {String} searchQuery
         * @returns {*}
         */
        updateNearbyLocations: function (searchQuery) {
            var self = this,
                productsInfo = [],
                items = quote.getItems(),
                searchCriteria;

            _.each(items, function (item) {
                if (item['qty_options'] === undefined || item['qty_options'].length === 0) {
                    productsInfo.push(
                        {
                            sku: item.sku
                        }
                    );
                }
            });

            searchCriteria = {
                extensionAttributes: {
                    productsInfo: productsInfo
                },
                pageSize: this.nearbySearchLimit
            };

            if (searchQuery) {
                searchCriteria.area = {
                    radius: this.nearbySearchRadius,
                    searchTerm: searchQuery
                };
            }

            return pickupLocationsService
                .getNearbyLocations(searchCriteria)
                .then(function (locations) {
                    self.nearbyLocations(locations);
                })
                .fail(function () {
                    self.nearbyLocations([]);
                });
        },

        /**
         * @returns {Boolean}
         */
        validatePickupInformation: function () {
            var emailValidationResult,
                loginFormSelector = this.loginFormSelector;

            if (!customer.isLoggedIn()) {
                $(loginFormSelector).validation();
                emailValidationResult = $(loginFormSelector + ' input[name=username]').valid() ? true : false;

                if (!emailValidationResult) {
                    $(this.loginFormSelector + ' input[name=username]').focus();

                    return false;
                }
            }

            return true;
        }
    });
});
