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
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/shipping-rate-service',
    'Magento_InventoryInStorePickupFrontend/js/model/shipping-rate-processor/store-pickup-address',
    'Magento_InventoryInStorePickupFrontend/js/model/pickup-locations-service'
], function (
    Component,
    _,
    $,
    ko,
    registry,
    quote,
    selectShippingMethodAction,
    selectShippingAddressAction,
    checkoutData,
    shippingService,
    stepNavigator,
    shippingRateService,
    shippingRateProcessor,
    pickupLocationsService
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_InventoryInStorePickupFrontend/store-pickup',
            deliveryMethodSelectorTemplate: 'Magento_InventoryInStorePickupFrontend/delivery-method-selector',
            isVisible: false,
            isAvailable: false,
            isStorePickupSelected: false,
            lastNonPickupShippingAddress: null,
            rate: {
                'carrier_code': 'instore',
                'method_code': 'pickup'
            },
            nearbySearchLimit: 50,
            defaultCountry: window.checkoutConfig.defaultCountryId,
            delimiter: window.checkoutConfig.storePickupApiSearchTermDelimiter,
            rates: shippingService.getShippingRates()
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();

            shippingRateService.registerProcessor('store-pickup-address', shippingRateProcessor);

            quote.shippingAddress.subscribe(function (shippingAddress) {
                this.saveLastShippingAddress(shippingAddress);
            }, this);
            this.saveLastShippingAddress(quote.shippingAddress());

            this.isStorePickupSelected.subscribe(function () {
                this.preselectLocation();
            }, this);
            this.preselectLocation();

            this.syncWithShipping();
        },

        /**
         * Init component observable variables
         *
         * @return {exports}
         */
        initObservable: function () {
            this._super().observe(['isVisible', 'lastNonPickupShippingAddress']);

            this.isStorePickupSelected = ko.pureComputed(function () {
                return _.isMatch(quote.shippingMethod(), this.rate);
            }, this);

            this.isAvailable = ko.pureComputed(function () {
                return _.findWhere(this.rates(), {
                    'carrier_code': this.rate['carrier_code'],
                    'method_code': this.rate['method_code']
                });
            }, this);

            return this;
        },

        /**
         * Synchronize store pickup visibility with shipping step.
         *
         * @returns void
         */
        syncWithShipping: function () {
            var shippingStep = _.findWhere(stepNavigator.steps(), {
                code: 'shipping'
            });

            shippingStep.isVisible.subscribe(function (isShippingVisible) {
                this.isVisible(this.isAvailable && isShippingVisible);
            }, this);
            this.isVisible(this.isAvailable && shippingStep.isVisible());
        },

        /**
         * @returns void
         */
        selectShipping: function () {
            var nonPickupShippingMethod = _.find(
                this.rates(),
                function (rate) {
                    return (
                        rate['carrier_code'] !== this.rate['carrier_code'] &&
                        rate['method_code'] !== this.rate['method_code']
                    );
                },
                this
            ) || null;

            this.restoreLastShippingAddress();
            this.selectShippingMethod(nonPickupShippingMethod);
        },

        /**
         * @returns void
         */
        selectStorePickup: function () {
            var pickupShippingMethod = _.findWhere(
                this.rates(),
                {
                    'carrier_code': this.rate['carrier_code'],
                    'method_code': this.rate['method_code']
                },
                this
            );

            this.saveLastShippingAddress(quote.shippingAddress());
            this.preselectLocation();
            this.selectShippingMethod(pickupShippingMethod);
            checkoutData.setSelectedShippingRate(this.rate['carrier_code'] + '_' + this.rate['method_code']);
        },

        /**
         * @param {Object} shippingMethod
         * @returns void
         */
        selectShippingMethod: function (shippingMethod) {
            selectShippingMethodAction(shippingMethod);
            checkoutData.setSelectedShippingRate(
                shippingMethod ?
                    shippingMethod['carrier_code'] + '_' + shippingMethod['method_code'] :
                    null
            );
        },

        /**
         * @param {Object} shippingAddress
         * @returns void
         */
        saveLastShippingAddress: function (shippingAddress) {
            if (!this.isStorePickupAddress(shippingAddress)) {
                this.lastNonPickupShippingAddress(shippingAddress);
            }
        },

        /**
         * @returns void
         */
        restoreLastShippingAddress: function () {
            var shippingAddress = this.lastNonPickupShippingAddress();

            selectShippingAddressAction(shippingAddress);
            checkoutData.setSelectedShippingAddress(shippingAddress.getKey());
        },

        /**
         * @returns void
         */
        preselectLocation: function () {
            var selectedLocation,
                shippingAddress,
                customAttributes,
                selectedSourceCode,
                nearestLocation,
                productsInfo = [],
                items = quote.getItems();

            if (!this.isStorePickupSelected()) {
                return;
            }

            selectedLocation = pickupLocationsService.selectedLocation();

            if (selectedLocation) {
                pickupLocationsService.selectForShipping(selectedLocation);

                return;
            }

            shippingAddress = quote.shippingAddress();
            customAttributes = shippingAddress.customAttributes || [];
            selectedSourceCode = _.findWhere(customAttributes, {
                'attribute_code': 'sourceCode'
            });

            if (selectedSourceCode) {
                pickupLocationsService
                    .getLocation(selectedSourceCode.value)
                    .then(function (location) {
                        pickupLocationsService.selectForShipping(location);
                    });
            } else if (shippingAddress.city && shippingAddress.postcode) {
                _.each(items, function (item) {
                    if (item['qty_options'] === undefined || item['qty_options'].length === 0) {
                        productsInfo.push(
                            {
                                sku: item.sku
                            }
                        );
                    }
                });
                pickupLocationsService
                    .getNearbyLocations({
                        area: {
                            radius: this.nearbySearchRadius,
                            searchTerm: shippingAddress.postcode + this.delimiter +
                                        shippingAddress.countryId || this.defaultCountry
                        },
                        extensionAttributes: {
                            productsInfo: productsInfo
                        },
                        pageSize: this.nearbySearchLimit
                    })
                    .then(function (locations) {
                        nearestLocation = locations[0];

                        if (nearestLocation) {
                            pickupLocationsService.selectForShipping(
                                nearestLocation
                            );
                        }
                    });
            }
            this.convertAddressType(quote.shippingAddress());
        },

        /**
         * @param {Object} address
         * @returns {Boolean}
         */
        isStorePickupAddress: function (address) {
            return address.getType() === 'store-pickup-address';
        }
    });
});
