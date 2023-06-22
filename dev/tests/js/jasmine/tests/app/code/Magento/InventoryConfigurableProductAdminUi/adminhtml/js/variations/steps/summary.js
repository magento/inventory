/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* eslint max-nested-callbacks: 0 */
/* jscs:disable jsDoc*/

define([
    'Magento_InventoryConfigurableProductAdminUi/js/variations/steps/summary'
], function (Summary) {
    'use strict';

    describe('Magento_InventoryConfigurableProductAdminUi/js/variations/steps/summary', function () {
        let model, quantityFieldName, productDataFromGrid, productDataFromGridExpected;

        beforeEach(function () {
            quantityFieldName = 'quantityPerSource989898';
            model = new Summary({quantityFieldName: quantityFieldName});
            productDataFromGrid = {
                sku: 'testSku',
                name: 'test name',
                weight: 12.12312,
                status: 1,
                price: 333.333,
                someField: 'someValue',
                quantity: 10,
                qty: 20
            };

            productDataFromGrid[quantityFieldName] = {d: 3333, btg: 5234, obj: {data: 'string data'}};

            productDataFromGridExpected = {
                sku: 'testSku',
                name: 'test name',
                weight: 12.12312,
                status: 1,
                price: 333.333
            };
        });

        describe('Check prepareProductDataFromGrid', function () {

            it('Check call to prepareProductDataFromGrid method with qty', function () {
                productDataFromGrid.quantity_per_source = {a: 'abc', b: 324, obj: {data: 'string data', obj2: {hh: 2}}};
                productDataFromGridExpected[quantityFieldName] = {
                    a: 'abc',
                    b: 324,
                    obj: {data: 'string data', obj2: {hh: 2}}
                };
                const result = model.prepareProductDataFromGrid(productDataFromGrid);

                expect(result).toEqual(productDataFromGridExpected);
            });


            it('Check call to prepareProductDataFromGrid method without qty', function () {
                const result = model.prepareProductDataFromGrid(productDataFromGrid);

                expect(result).toEqual(productDataFromGridExpected);
            });
        });
    });
});
