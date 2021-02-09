/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/*eslint-disable max-nested-callbacks*/
/*jscs:disable jsDoc*/
define([
    'Magento_InventoryInStorePickupFrontend/js/model/pickup-address-converter'
], function (pickupAddressConverter) {
    'use strict';

    var address;

    describe('Magento_InventoryInStorePickupFrontend/js/model/pickup-address-converter', function () {
        describe('formatAddressToPickupAddress()', function () {
            beforeEach(function () {
                address = {
                    countryId: 'US',
                    saveInAddressBook: 1,
                    extensionAttributes: {
                        'pickup_location_code': 'la_ca_us'
                    },
                    getType: function () {
                        return 'new-customer-address';
                    },
                    getKey: function () {
                        return this.getType();
                    },
                    canUseForBilling: function () {
                        return true;
                    }
                };
            });
            it('address is converted if it has pickup_location_code extension attribute', function () {
                var result = pickupAddressConverter.formatAddressToPickupAddress(address);

                expect(result).not.toBe(address);
                expect(result.saveInAddressBook).toEqual(0);
                expect(result.canUseForBilling()).toEqual(false);
                expect(result.getType()).toEqual('store-pickup-address');
                expect(result.getKey()).toEqual('store-pickup-addressla_ca_us');
            });
            it('address is not converted if it has not pickup_location_code extension attribute', function () {
                var result;

                address.extensionAttributes = {};
                result = pickupAddressConverter.formatAddressToPickupAddress(address);
                expect(result).toBe(address);
                expect(result.saveInAddressBook).toEqual(1);
                expect(result.canUseForBilling()).toEqual(true);
                expect(result.getType()).toEqual('new-customer-address');
                expect(result.getKey()).toEqual('new-customer-address');
            });
            it('address is not converted if is a pickup address already', function () {
                var result;

                address.getType = function () {
                    return 'store-pickup-address';
                };
                result = pickupAddressConverter.formatAddressToPickupAddress(address);
                expect(result).toBe(address);
                expect(result.saveInAddressBook).toEqual(1);
                expect(result.canUseForBilling()).toEqual(true);
            });
        });
    });
});
