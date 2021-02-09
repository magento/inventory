/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/*eslint-disable max-nested-callbacks*/
/*jscs:disable jsDoc*/
define([
    'squire',
    'ko',
    'underscore'
], function (Squire, ko, _) {
    'use strict';

    describe('Magento_InventoryInStorePickupFrontend/js/model/checkout-data-resolver-ext', function () {
        var injector = new Squire(),
            checkoutDataResolverExtended = null,
            checkoutDataResolver = null,
            resolveShippingAddress = null,
            checkoutData = null,
            quote = null,
            mocks = null,
            pickupAddress = {
                firstname: 'Warehouse 425',
                lastname: 'Store',
                street: {
                    0: '1378  Zimmerman Lane'
                },
                city: 'Los Angeles',
                postcode: '90071',
                'country_id': 'US',
                telephone: '213-391-6626',
                'region_id': 12,
                'save_in_address_book': 0,
                'extension_attributes': {
                    'pickup_location_code': 'la_ca_us'
                }
            };

        beforeEach(function (done) {
            quote = {
                shippingAddress: ko.observable(null),
                isVirtual: jasmine.createSpy().and.returnValue(false),
                billingAddress: ko.observable(null),
                shippingMethod: ko.observable(null)
            };
            mocks = {
                'Magento_Checkout/js/checkout-data': {
                    getSelectedShippingAddress: jasmine.createSpy(),
                    getSelectedPickupAddress: jasmine.createSpy()
                },
                'Magento_Checkout/js/action/select-shipping-address': function (address) {
                    quote.shippingAddress(address);
                }
            };

            injector.mock(mocks);
            injector.require(
                ['Magento_InventoryInStorePickupFrontend/js/model/checkout-data-resolver-ext'],
                function (checkoutDataResolverExt) {
                    checkoutDataResolver = jasmine.createSpyObj('checkoutDataResolver', ['resolveShippingAddress']);
                    resolveShippingAddress = checkoutDataResolver.resolveShippingAddress;
                    checkoutDataResolverExtended = checkoutDataResolverExt(checkoutDataResolver);
                    checkoutData = mocks['Magento_Checkout/js/checkout-data'];
                    done();
                });
            window.checkoutConfig = window.checkoutConfig || {};
        });

        afterEach(function () {
            try {
                injector.clean();
                injector.remove();
            } catch (e) {
            }
            delete window.checkoutConfig.billingAddressFromData;
            delete window.checkoutConfig.isBillingAddressFromDataValid;
        });

        describe('resolveShippingAddress()', function () {
            describe(
                'shipping address is resolved as pickup address if selected address matches selected pickup address',
                function () {
                    it('selected pickup address is set as shipping address', function () {
                        checkoutData.getSelectedShippingAddress.and.returnValue('store-pickup-addressla_ca_us');
                        checkoutData.getSelectedPickupAddress.and.returnValue(pickupAddress);
                        checkoutDataResolverExtended.resolveShippingAddress();
                        expect(quote.shippingAddress().firstname).toEqual(pickupAddress.firstname);
                        expect(quote.shippingAddress().lastname).toEqual(pickupAddress.lastname);
                        expect(quote.shippingAddress().street).toEqual(_.toArray(pickupAddress.street));
                        expect(quote.shippingAddress().city).toEqual(pickupAddress.city);
                        expect(quote.shippingAddress().postcode).toEqual(pickupAddress.postcode);
                        expect(quote.shippingAddress().telephone).toEqual(pickupAddress.telephone);
                        expect(quote.shippingAddress().regionId).toEqual(pickupAddress['region_id']);
                        expect(quote.shippingAddress().countryId).toEqual(pickupAddress['country_id']);
                        expect(quote.shippingAddress().saveInAddressBook).toEqual(0);
                        expect(quote.shippingAddress().canUseForBilling()).toEqual(false);
                        expect(quote.shippingAddress().getType()).toEqual('store-pickup-address');
                        expect(quote.shippingAddress().getKey()).toEqual('store-pickup-addressla_ca_us');
                        expect(resolveShippingAddress).not.toHaveBeenCalled();
                    });
                    it(
                        'shipping address is not resolved as pickup address if selected shipping address' +
                        ' is not a pickup address',
                        function () {
                        checkoutData.getSelectedShippingAddress.and.returnValue('new-customer-address');
                        checkoutData.getSelectedPickupAddress.and.returnValue(pickupAddress);
                        checkoutDataResolverExtended.resolveShippingAddress();
                        expect(quote.shippingAddress()).toBeNull();
                        expect(resolveShippingAddress).toHaveBeenCalled();
                    });
                    it(
                        'shipping address is not resolved as pickup address if no pickup address is selected',
                        function () {
                            checkoutData.getSelectedShippingAddress.and.returnValue('store-pickup-addressla_ca_us');
                            checkoutData.getSelectedPickupAddress.and.returnValue(null);
                            checkoutDataResolverExtended.resolveShippingAddress();
                            expect(quote.shippingAddress()).toBeNull();
                            expect(resolveShippingAddress).toHaveBeenCalled();
                        }
                    );
                }
            );
        });
    });
});
