/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/*eslint-disable max-nested-callbacks*/
/*jscs:disable jsDoc*/
define([
    'squire',
    'ko'
], function (Squire, ko) {
    'use strict';

    describe('Magento_InventoryInStorePickupFrontend/js/model/pickup-locations-service', function () {
        var injector = new Squire(),
            pickupLocationsService = null,
            checkoutData = null,
            quote = null,
            mocks = null;

        beforeEach(function (done) {
            quote = {
                shippingAddress: ko.observable(null),
                isVirtual: jasmine.createSpy().and.returnValue(false),
                billingAddress: ko.observable(null),
                shippingMethod: ko.observable(null)
            };
            mocks = {
                'Magento_InventoryInStorePickupFrontend/js/model/resource-url-manager': {},
                'mage/storage': {},
                'Magento_Customer/js/customer-data': {
                    get: function (sectionName) {
                        return {
                            'directory-data': ko.observable({
                                'US': {
                                    name: 'United States',
                                    regions: {
                                        12: {
                                            name: 'California'
                                        },
                                        57: {
                                            name: 'Texas'
                                        }
                                    }
                                }
                            })
                        }[sectionName];
                    }
                },
                'Magento_Checkout/js/checkout-data': {
                    setSelectedPickupAddress: jasmine.createSpy(),
                    setSelectedShippingAddress: jasmine.createSpy()
                },
                'Magento_Checkout/js/action/select-shipping-address': function (address) {
                    quote.shippingAddress(address);
                }
            };

            window.checkoutConfig = window.checkoutConfig || {};
            window.checkoutConfig.websiteCode = 'US';
            injector.mock(mocks);
            injector.require(
                ['Magento_InventoryInStorePickupFrontend/js/model/pickup-locations-service'],
                function (instance) {
                    pickupLocationsService = instance;
                    checkoutData = mocks['Magento_Checkout/js/checkout-data'];
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
        });

        describe('selectForShipping()', function () {
            it('shipping address is set', function () {
                var location = {
                    name: 'Warehouse 425',
                    street: ['1378  Zimmerman Lane'],
                    city: 'Los Angeles',
                    postcode: '90071',
                    telephone: '213-391-6626',
                    'country_id': 'US',
                    'region_id': 12,
                    'pickup_location_code': 'la_ca_us'
                };

                pickupLocationsService.selectForShipping(location);
                expect(quote.shippingAddress().firstname).toEqual(location.name);
                expect(quote.shippingAddress().lastname).toEqual('Store');
                expect(quote.shippingAddress().street).toEqual(location.street);
                expect(quote.shippingAddress().city).toEqual(location.city);
                expect(quote.shippingAddress().postcode).toEqual(location.postcode);
                expect(quote.shippingAddress().telephone).toEqual(location.telephone);
                expect(quote.shippingAddress().regionId).toEqual(location['region_id']);
                expect(quote.shippingAddress().countryId).toEqual(location['country_id']);
                expect(quote.shippingAddress().saveInAddressBook).toEqual(0);
                expect(quote.shippingAddress().canUseForBilling()).toEqual(false);
                expect(quote.shippingAddress().getType()).toEqual('store-pickup-address');
                expect(quote.shippingAddress().getKey()).toEqual('store-pickup-addressla_ca_us');

                expect(checkoutData.setSelectedShippingAddress).toHaveBeenCalledWith('store-pickup-addressla_ca_us');
                expect(checkoutData.setSelectedPickupAddress).toHaveBeenCalledWith(jasmine.objectContaining({
                    firstname: location.name,
                    lastname: 'Store',
                    street: {
                        0: location.street[0]
                    },
                    city: location.city,
                    postcode: location.postcode,
                    'country_id': location['country_id'],
                    telephone: location.telephone,
                    'region_id': location['region_id'],
                    'save_in_address_book': 0,
                    'extension_attributes': {
                        'pickup_location_code': location['pickup_location_code']
                    }
                }));
            });
        });
    });
});
