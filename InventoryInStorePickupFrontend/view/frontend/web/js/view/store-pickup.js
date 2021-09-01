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
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/shipping-rate-service',
    'Magento_InventoryInStorePickupFrontend/js/model/shipping-rate-processor/store-pickup-address',
    'Magento_InventoryInStorePickupFrontend/js/model/pickup-locations-service',
    'Magento_InventoryInStorePickupFrontend/js/model/pickup-address-converter',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Checkout/js/action/select-shipping-address'
], function (
    Component,
    _,
    $,
    ko,
    registry,
    quote,
    selectShippingMethodAction,
    checkoutData,
    shippingService,
    stepNavigator,
    shippingRateService,
    shippingRateProcessor,
    pickupLocationsService,
    pickupAddressConverter,
    checkoutDataResolver,
    selectShippingAddress
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magento_InventoryInStorePickupFrontend/store-pickup',
            deliveryMethodSelectorTemplate: 'Magento_InventoryInStorePickupFrontend/delivery-method-selector',
            isVisible: false,
            isAvailable: false,
            isStorePickupSelected: false,
            rate: {
                'carrier_code': 'instore',
                'method_code': 'pickup'
            },
            nearbySearchLimit: 50,
            defaultCountry: window.checkoutConfig.defaultCountryId,
            delimiter: window.checkoutConfig.storePickupApiSearchTermDelimiter,
            rates: shippingService.getShippingRates(),
            inStoreMethod: null,
            lastSelectedNonPickUpShippingAddress: null
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();

            shippingRateService.registerProcessor('store-pickup-address', shippingRateProcessor);

            this.convertAddressType(quote.shippingAddress());

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
            this._super().observe(['isVisible']);

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
            ),
                nonPickupShippingAddress;

            checkoutData.setSelectedShippingAddress(this.lastSelectedNonPickUpShippingAddress);
            this.selectShippingMethod(nonPickupShippingMethod);

            if (this.isStorePickupAddress(quote.shippingAddress())) {
                nonPickupShippingAddress = checkoutDataResolver.getShippingAddressFromCustomerAddressList();

                if (nonPickupShippingAddress) {
                    selectShippingAddress(nonPickupShippingAddress);
                }
            }
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

            this.lastSelectedNonPickUpShippingAddress = checkoutData.getSelectedShippingAddress();
            checkoutData.setSelectedShippingAddress(null);
            this.preselectLocation();
            this.selectShippingMethod(pickupShippingMethod);
        },

        /**
         * @param {Object} shippingMethod
         */
        selectShippingMethod: function (shippingMethod) {
            selectShippingMethodAction(shippingMethod);
            checkoutData.setSelectedShippingRate(
                shippingMethod ? shippingMethod['carrier_code'] + '_' + shippingMethod['method_code'] : null
            );
        },

        /**
         * @param {Object} shippingAddress
         * @returns void
         */
        convertAddressType: function (shippingAddress) {
            var pickUpAddress;

            if (
                !this.isStorePickupAddress(shippingAddress) &&
                this.isStorePickupSelected()
            ) {
                pickUpAddress = pickupAddressConverter.formatAddressToPickupAddress(shippingAddress);

                if (quote.shippingAddress() !== pickUpAddress) {
                    quote.shippingAddress(pickUpAddress);
                }
            }
        },

        /**
         * @returns void
         */
        preselectLocation: function () {
            var selectedLocation,
                shippingAddress,
                selectedPickupAddress,
                customAttributes,
                selectedSource,
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
            selectedSource = _.findWhere(customAttributes, {
                'attribute_code': 'sourceCode'
            });

            if (selectedSource) {
                selectedSourceCode = selectedSource.value;
            }

            if (!selectedSourceCode) {
                selectedSourceCode = this.getPickupLocationCodeFromAddress(shippingAddress);
            }

            if (!selectedSourceCode) {
                selectedPickupAddress = pickupLocationsService.getSelectedPickupAddress();
                selectedSourceCode = this.getPickupLocationCodeFromAddress(selectedPickupAddress);
            }

            if (selectedSourceCode) {
                pickupLocationsService
                    .getLocation(selectedSourceCode)
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
        },

        /**
         * @param {Object} address
         * @returns {Boolean}
         */
        isStorePickupAddress: function (address) {
            return address.getType() === 'store-pickup-address';
        },

        /**
         * @param {Object} address
         * @returns {String|null}
         */
        getPickupLocationCodeFromAddress: function (address) {
            if (address &&
                !_.isEmpty(address.extensionAttributes) &&
                address.extensionAttributes['pickup_location_code']
            ) {
                return address.extensionAttributes['pickup_location_code'];
            }

            return null;
        }
    });
});
