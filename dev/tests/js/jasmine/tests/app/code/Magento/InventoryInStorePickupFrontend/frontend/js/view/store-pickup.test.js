/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/*eslint-disable max-nested-callbacks*/
/*jscs:disable jsDoc*/
define([
    'squire',
    'ko',
    'jquery'
], function (Squire, ko, $) {
    'use strict';

    describe('Magento_InventoryInStorePickupFrontend/js/view/store-pickup', function () {
        var injector = new Squire(),
            storePickup,
            checkoutData,
            quote,
            shippingService,
            shippingRateService,
            pickupLocationsService,
            checkoutDataResolver,
            stepNavigator,
            mocks,
            shippingRates = [
                {
                    'carrier_code': 'instore',
                    'method_code': 'pickup'
                },
                {
                    'carrier_code': 'flatrate',
                    'method_code': 'flatrate'
                }
            ],
            location = {
                name: 'Warehouse 425',
                street: ['1378  Zimmerman Lane'],
                city: 'Los Angeles',
                postcode: '90071',
                telephone: '213-391-6626',
                'country_id': 'US',
                'region_id': 12,
                'pickup_location_code': 'la_ca_us'
            },
            shippingAddress = {
                firstname: 'John',
                lastname: 'Doe',
                street: ['3640 Holdrege Ave'],
                city: 'Los Angeles',
                postcode: '90016',
                countryId: 'US',
                telephone: '555-256-2605',
                regionId: 12,
                saveInAddressBook: 1,
                getType: function () {
                    return 'new-customer-address';
                },
                getKey: function () {
                    return 'new-customer-address';
                },
                canUseForBilling: function () {
                    return true;
                }
            },
            pickupAddress = {
                firstname: 'Warehouse 425',
                lastname: 'Store',
                street: ['1378  Zimmerman Lane'],
                city: 'Los Angeles',
                postcode: '90071',
                countryId: 'US',
                telephone: '213-391-6626',
                regionId: 12,
                saveInAddressBook: 0,
                extensionAttributes: {
                    'pickup_location_code': 'la_ca_us'
                },
                getType: function () {
                    return 'store-pickup-address';
                },
                getKey: function () {
                    return 'store-pickup-addressla_ca_us';
                },
                canUseForBilling: function () {
                    return false;
                }
            };

        beforeEach(function (done) {
            quote = {
                shippingAddress: ko.observable(null),
                isVirtual: jasmine.createSpy().and.returnValue(false),
                billingAddress: ko.observable(null),
                shippingMethod: ko.observable(null),
                getItems: function () {
                    return [
                        {
                            sku: 'P132'
                        },
                        {
                            sku: 'P242'
                        }
                    ];
                }
            };
            checkoutData = jasmine.createSpyObj(
                'checkoutData',
                ['getSelectedShippingAddress', 'setSelectedShippingAddress', 'setSelectedShippingRate']
            );
            shippingRateService = jasmine.createSpyObj(
                'shippingRateService',
                ['registerProcessor']
            );
            pickupLocationsService = jasmine.createSpyObj(
                'pickupLocationsService',
                ['selectForShipping', 'getLocation', 'getNearbyLocations']
            );
            pickupLocationsService.selectedLocation = ko.observable(null);
            shippingService = jasmine.createSpyObj(
                'shippingService',
                ['getShippingRates']
            );
            shippingService.getShippingRates.and.returnValue(ko.observable(shippingRates));
            stepNavigator = {
                steps: ko.observableArray([
                    {
                        code: 'shipping',
                        isVisible: ko.observable(true)
                    }
                ])
            };
            checkoutDataResolver = jasmine.createSpyObj(
                'checkoutDataResolver',
                ['getShippingAddressFromCustomerAddressList']
            );
            mocks = {
                'uiRegistry': {},
                'Magento_Checkout/js/model/quote': quote,
                'Magento_Checkout/js/action/select-shipping-method': function (address) {
                    quote.shippingMethod(address);
                },
                'Magento_Checkout/js/checkout-data': checkoutData,
                'Magento_Checkout/js/model/shipping-service': shippingService,
                'Magento_Checkout/js/model/step-navigator': stepNavigator,
                'Magento_Checkout/js/model/shipping-rate-service': shippingRateService,
                'Magento_InventoryInStorePickupFrontend/js/model/pickup-locations-service': pickupLocationsService,
                'Magento_Checkout/js/model/checkout-data-resolver': checkoutDataResolver,
                'Magento_Checkout/js/action/select-shipping-address': function (address) {
                    quote.shippingAddress(address);
                }
            };

            window.checkoutConfig = window.checkoutConfig || {};
            window.checkoutConfig.storePickupApiSearchTermDelimiter = ':';
            window.checkoutConfig.defaultCountryId = 'US';

            injector.mock(mocks);
            injector.require(
                ['Magento_InventoryInStorePickupFrontend/js/view/store-pickup'],
                function (StorePickupConstructor) {
                    quote.shippingAddress($.extend(true, {}, shippingAddress));
                    storePickup = new StorePickupConstructor();
                    storePickup.isStorePickupSelected = ko.observable(false);
                    done();
                }
            );
        });

        afterEach(function () {
            try {
                injector.clean();
                injector.remove();
            } catch (e) {
            }
            delete window.checkoutConfig.storePickupApiSearchTermDelimiter;
            delete window.checkoutConfig.defaultCountryId;
        });

        describe('preselectLocation()', function () {
            it('nearest pickup address is preselected if shipping address is defined', function () {
                storePickup.isStorePickupSelected(true);
                pickupLocationsService.getNearbyLocations.and.callFake(function () {
                    var deferred = $.Deferred();

                    deferred.resolve([location]);

                    return deferred.promise();
                });
                storePickup.preselectLocation();
                expect(pickupLocationsService.getLocation).not.toHaveBeenCalled();
                expect(pickupLocationsService.getNearbyLocations).toHaveBeenCalledWith(jasmine.objectContaining({
                    area: {
                        radius: this.nearbySearchRadius,
                        searchTerm: shippingAddress.postcode + ':' + shippingAddress.countryId
                    },
                    extensionAttributes: {
                        productsInfo: [
                            {
                                sku: 'P132'
                            },
                            {
                                sku: 'P242'
                            }
                        ]
                    },
                    pageSize: 50
                }));
                expect(pickupLocationsService.selectForShipping).toHaveBeenCalledWith(location);
            });

            it('pickup address is preselected if shipping address is a pickup address', function () {
                quote.shippingAddress($.extend(true, {}, pickupAddress));
                storePickup.isStorePickupSelected(true);
                pickupLocationsService.getLocation.and.callFake(function () {
                    var deferred = $.Deferred();

                    deferred.resolve(location);

                    return deferred.promise();
                });
                storePickup.preselectLocation();
                expect(pickupLocationsService.getLocation).toHaveBeenCalledWith('la_ca_us');
                expect(pickupLocationsService.selectForShipping).toHaveBeenCalledWith(location);
                expect(pickupLocationsService.getNearbyLocations).not.toHaveBeenCalled();
            });

            it('no pickup address is selected if "in store pickup" is not selected', function () {
                storePickup.preselectLocation();
                expect(pickupLocationsService.getLocation).not.toHaveBeenCalled();
                expect(pickupLocationsService.selectForShipping).not.toHaveBeenCalled();
                expect(pickupLocationsService.getNearbyLocations).not.toHaveBeenCalled();
            });
        });
    });
});
